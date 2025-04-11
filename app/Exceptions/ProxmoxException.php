<?php

namespace App\Exceptions;

use Exception;

class ProxmoxException extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool
     */
    public function report()
    {
        return true;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Proxmox API Error',
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
        ], 500);
    }
}
