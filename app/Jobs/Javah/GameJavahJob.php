<?php

namespace App\Jobs\Javah;

use App\Jobs\Callback\Javah\CGameJavahJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GameJavahJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo "\nrecived data transaksi game\n";
        // create transaksi javah via api
        $content = $this->payload;
        $url = 'https://javah2h.com/api/connect/';
        $user_id = config('app.java_config.user_id');
        $key = config('app.java_config.key');
        $secret = config('app.java_config.secret');

        $header = [
            "h2h-userid: $user_id",
            "h2h-key: $key", // lihat hasil autogenerate di member area
            "h2h-secret: $secret", // lihat hasil autogenerate di member area
        ];

        $data = array(
            'inquiry' => 'I', // konstan
            'code' => $content['code'], // kode produk
            'phone' => $content['phone'], // nohp pembeli
            'trxid_api' => $content['trxid_api'], // Trxid / Reffid dari sisi client
            'no' => '1', // untuk isi lebih dari 1x dlm sehari, isi urutan 1,2,3,4,dst
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpcode != 200) {
            $callback = [
                'status' => 'failed',
                'trxid_api' => $content['trxid_api'],
                'note' => 'server javah error',
                'response' => $result,
            ];
        } else {
            $response = json_decode($result);
            if ($response->result == 'failed') {
                $callback = [
                    'status' => 'failed',
                    'trxid_api' => $content['trxid_api'],
                    'note' => $response->message,
                    'response' => $result,
                ];
            } else {
                $callback = [
                    'status' => 'success',
                    'trxid_api' => $content['trxid_api'],
                    'note' => '',
                    'response' => $result,
                ];
            }

        }

        echo json_encode($callback);
        // send callback ke loket pulsa
        CGameJavahJob::dispatch($callback);
    }
}
