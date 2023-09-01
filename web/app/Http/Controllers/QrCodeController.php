<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QrCode;
use Illuminate\Support\Facades\Log;
use App\Lib\QrCodeHelper;

class QrCodeController extends Controller
{
    private $qrCodeHelper;

    public function __construct(QrCodeHelper $qrCodeHelper)
    {
        $this->qrCodeHelper = $qrCodeHelper;
    }

    /**
     * Create a new QR code record.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create (Request $request)
    {
        $qrCodes = $request->all();
        $qrCodes['shopDomain'] = $request->get('shopDomain');
        $qrCodes['scans'] = null;

        $response = $code = null;
        try {
            $rowCodeData = [];
            $code = 201;
            $rowCodeData[] = QrCode::create($qrCodes)->toArray();
            $response = $this->qrCodeHelper->formatQrCodeResponse($rowCodeData)[0];

        } catch (\Exception $e) {
            $code = 500;
            $response = $e->getMessage();

            Log::error("Failed to create qrcodes: $response");

        } finally {
            return response()->json($response, $code);
        }
    }

    /**
     * get List QR code record.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index (Request $request)
    {
        $response = $code = null;
        try {
            $qrCodes = QrCode::where('shopDomain', $request->get('shopDomain'))->get()->toArray();
            $responseData = [];
            $code = 200;
            foreach ($qrCodes as $qrCode) {
                $responseData[] = $this->__addImageUrl($qrCode);
            }

            $response = $this->qrCodeHelper->formatQrCodeResponse($responseData);

        } catch (\Exception $e) {
            $code = 500;
            $response = $e->getMessage();

            Log::error("Failed to index qrcodes: $response");

        } finally {
            return response()->json($response, $code);
        }
    }

    /**
     * get QR code record by id.
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show ($id)
    {
        $qrCode = $this->qrCodeHelper->getQrCodeOr404($id);
        if ($qrCode) {
            $code = 200;
            $this->__addImageUrl($qrCode);
            $response = $this->qrCodeHelper->formatQrCodeResponse([$qrCode->toArray()])[0];
            return response()->json($response, $code);
        }
    }

    private function __addImageUrl($qrCode)
    {
        try {
            $qrCode['imageUrl'] = $this->__generateQrcodeImageUrl($qrCode);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return $qrCode;
    }

}
