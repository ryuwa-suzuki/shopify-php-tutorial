<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Shopify\Clients\Graphql;
use App\Models\QrCode;
use Exception;

class QrCodeHelper
{
    protected $request;

    private const QR_CODE_ADMIN_QUERY = <<<'QUERY'
    query nodes($ids: [ID!]!) {
        nodes(ids: $ids) {
            ... on Product {
                id
                handle
                title
                images(first: 1) {
                    edges {
                        node {
                            url
                        }
                    }
                }
            }
            ... on ProductVariant {
                id
            }
            ... on DiscountCodeNode {
                id
            }
        }
    }
    QUERY;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getQrCodeOr404($id, $checkDomain = true)
    {
        $session = $this->request->get('shopifySession');
        try {
            $response = QrCode::find($id);

            if ($response === null || ($checkDomain && 'https://'.$session->getShop() !== $response->shopDomain)) {
                throw new Exception();
            }

            return $response;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function formatQrCodeResponse($rawCodeData)
    {
        $session = $this->request->get('shopifySession');

        $ids = [];

        foreach ($rawCodeData as $qrCode) {
            $ids[] = $qrCode['productId'];
            $ids[] = $qrCode['variantId'];

            if ($qrCode['discountId']) {
                $ids[] = $qrCode['discountId'];
            }
        }

        $client = new Graphql($session->getShop(), $session->getAccessToken());

        $adminData = $client->query([
            'query' => self::QR_CODE_ADMIN_QUERY,
            'variables' => ['ids' => $ids],
        ])->getDecodedBody();

        $formattedData = [];

        foreach ($rawCodeData as $qrCode) {
            $product = null;

            foreach ($adminData['data']['nodes'] as $node) {
                if ($qrCode['productId'] === $node['id']) {
                    $product = $node;
                    break;
                }
            }

            if (!$product) {
                $product = ['title' => 'Deleted product'];
            }

            $discountDeleted = $qrCode['discountId'] && !in_array($qrCode['discountId'], array_column($adminData['data']['nodes'], 'id'));

            if ($discountDeleted) {
                QrCode::where('id', $qrCode['id'])
                ->update([
                    'discountId' => '',
                    'discountCode' => '',
                ]);
            }

            $formattedQRCode = array_merge($qrCode, [
                'product' => $product,
                'discountCode' => $discountDeleted ? '' : $qrCode['discountCode'],
            ]);

            unset($formattedQRCode['productId']);

            $formattedData[] = $formattedQRCode;
        }

        return $formattedData;
    }
}
