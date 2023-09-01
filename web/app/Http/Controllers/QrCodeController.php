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
}
