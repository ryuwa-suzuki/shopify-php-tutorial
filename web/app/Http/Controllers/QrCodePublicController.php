<?php

namespace App\Http\Controllers;

use App\Lib\QrCodeHelper;
use Shopify\Context;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Redirect;

class QrCodePublicController extends Controller
{
    private $qrCodeHelper;
    private const DEFAULT_PURCHASE_QUANTITY = 1;

    public function __construct(QrCodeHelper $qrCodeHelper)
    {
        $this->qrCodeHelper = $qrCodeHelper;
    }

    public function applyQrCodePublic($id)
    {
        $qrCode = $this->qrCodeHelper->getQrCodeOr404($id, false);
        if ($qrCode) {
            $destinationUrl = $this->__generateQrcodeDestinationUrl($qrCode);
            $image = QrCode::size(150)
                ->gradient(rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255), 'vertical')
                ->format('png')
                ->generate($destinationUrl);
            $headers = [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="qr_code_' . $qrCode->id . '.png"',
            ];

            return response($image, 200, $headers);
        }
    }

    public function scan($id)
    {
        $qrCode = $this->qrCodeHelper->getQrCodeOr404($id, false);
        $qrCode->where('id', $id)
            ->update([
                'scans' => $qrCode->scans + 1
            ]);
        $url = $qrCode->shopDomain;
        switch ($qrCode->destination) {
            case "product":
                return $this->__goToProductView($url, $qrCode);
            case "checkout":
                return $this->__goToProductCheckout($url, $qrCode);
            default:
                return 'Unrecognized destination.qrCode.destination';
        }
    }

    private function __generateQrcodeDestinationUrl($qrCode)
    {
        return Context::$HOST_SCHEME.'://'.Context::$HOST_NAME.'/qrcodes/'.$qrCode['id'].'/scan';
    }

    private function __goToProductView($url, $qrCode)
    {
        $discountCode = $qrCode['discountCode'];
        $productHandle = $qrCode['handle'];

        $urlString = $this->__productViewURL($url, $productHandle, $discountCode);

        return Redirect::to($urlString);
    }

    private function __goToProductCheckout($url, $qrCode)
    {
        $discountCode = $qrCode['discountCode'];
        $variantId = $qrCode['variantId'];
        $quantity = self::DEFAULT_PURCHASE_QUANTITY;

        $urlString = $this->__productCheckoutURL($url, $variantId, $quantity, $discountCode);

        return Redirect::to($urlString);
    }


    private function __productViewURL($url, $productHandle, $discountCode = null)
    {
        $productPath = '/products/'.urlencode($productHandle);

        if ($discountCode) {
            $redirectUrl = $url.'/discount/'.$discountCode.'?redirect='.$productPath;
        } else {
            $redirectUrl = $url.$productPath;
        }

        return $redirectUrl;
    }

    private function __productCheckoutURL ($url, $variantId, $quantity = 1, $discountCode = null)
    {
        $id = preg_replace('/gid:\/\/shopify\/ProductVariant\/([0-9]+)/', '$1', $variantId);
        $cartPath = "/cart/{$id}:{$quantity}";

        $redirectUrl = $url.$cartPath;

        if ($discountCode) {
            $redirectUrl .= '?discount=' . urlencode($discountCode);
        }

        return $redirectUrl;
    }
}
