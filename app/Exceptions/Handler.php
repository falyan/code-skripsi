<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($this->isHttpException($e)) {
            if ($e instanceof NotFoundHttpException) {
                return response(['status' => 'ERROR', 'message' => "Halaman tidak ditemukan", 'error_code' => 404], 400);
            } elseif ($e instanceof RequestExceptionInterface) {
                return response(['status' => 'ERROR', 'message' => "Gagal melakukan request ke server. File {$e->getFile()}:{$e->getLine()} with message {$e->getMessage()}", 'error_code' => $e->getStatusCode()], 400);
            } elseif ($e instanceof ServerExceptionInterface) {
                return response(['status' => 'ERROR', 'message' => "Kesalahan internal Server. File {$e->getFile()}:{$e->getLine()} with message {$e->getMessage()}", 'error_code' => $e->getStatusCode()], 400);
            } else {
                switch ($e->getStatusCode()) {
                    case '400':
                        return response(['status' => 'ERROR', 'message' => "Bad Request", 'error_code' => $e->getStatusCode()], 400);
                        break;

                        // not authorized
                    case '401':
                        return response(['status' => 'ERROR', 'message' => "Akses Tidak Diperbolehkan", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '402':
                        return response(['status' => 'ERROR', 'message' => "Pembayaran diperlukan", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '403':
                        return response(['status' => 'ERROR', 'message' => "Akses Dilarang", 'error_code' => $e->getStatusCode()], 400);
                        break;

                        // not found
                    case '404':
                        return response(['status' => 'ERROR', 'message' => "Halaman Tidak Ditemukan", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '405':
                        return response(['status' => 'ERROR', 'message' => "Metode Tidak Diizinkan", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '406':
                        return response(['status' => 'ERROR', 'message' => "Kontent Tidak Dapat Diterima", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '407':
                        return response(['status' => 'ERROR', 'message' => "Diperlukan Otentikasi Proksi", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '408':
                        return response(['status' => 'ERROR', 'message' => "Request Timeout", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '409':
                        return response(['status' => 'ERROR', 'message' => "Terjadi konflk", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '410':
                        return response(['status' => 'ERROR', 'message' => "Konten hilang atau tidak tersedia", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '500':
                        return response(['status' => 'ERROR', 'message' => "Kesalahan Internal Server", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '501':
                        return response(['status' => 'ERROR', 'message' => "Request belum tersedia pada server", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '502':
                        return response(['status' => 'ERROR', 'message' => "Bad Gateway", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '503':
                        return response(['status' => 'ERROR', 'message' => "Service tidak tersedia", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '504':
                        return response(['status' => 'ERROR', 'message' => "Gateway Timeout", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '505':
                        return response(['status' => 'ERROR', 'message' => "Versi HTTP Tidak didukung", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    case '440':
                        return response(['status' => 'ERROR', 'message' => "Token Autentikasi Kadaluwarsa", 'error_code' => $e->getStatusCode()], 400);
                        break;

                    default:
                        return response(['status' => 'ERROR', 'message' => "Unknown Error File {$e->getFile()}:{$e->getLine()} with message {$e->getMessage()}", 'error_code' => $e->getStatusCode()], $e->getStatusCode());
                        break;
                }
            }
        } else {
            if ($e instanceof \PDOException) {
                return response(['status' => 'ERROR', 'message' => "Sedang ada gangguan di jaringan atau sistem. Mohon coba kembali beberapa saat lagi. - MKP01", 'error_code' => 500], 500);
            } else {
                return parent::render($request, $e);
            }
        }
    }
}
