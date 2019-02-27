<?php
namespace T8891\ExportCsv;

use Closure;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCsvIfClientAccept
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (strpos($request->header('Accept'),'text/csv') === false) {
            return $next($request);
        }

        $request->offsetUnset('page');
        $request->offsetSet('pagesize', '-1');
        
        $payload = [ 'request' => $request ];
        app('events')->dispatch('export.csv.exporting', $payload);

        $response = $this->streamDownload(
            $this->convertResponseToCsv($next($request))
        );

        $payload['response'] = $response;
        app('events')->dispatch('export.csv.exported', $payload);

        return $response;
    }

    /**
     * 转换 response 为 Csv
     * 
     * @param  \Illuminate\Http\Response  $response
     * @return mixed
     */
    private function convertResponseToCsv($response)
    {
        if (!$response) return $response;

        $data = data_get(json_decode($response->content(), true), "data");

        $columns = collect($data[0])->keys()->all();
        $rows = collect($data)->map(function($row){
            return collect($row)->values();
        })->values()->toArray();

        $csv = Writer::createFromString('');
        $csv->insertOne($columns);
        $csv->insertAll($rows);
        return $csv;
    }

    /**
     * 串流下载
     * @param League\Csv\Writer $csv
     * @param string $charset
     * @param int $flush_threshold
     * @return \Illuminate\Http\Response  $response
     */
    private function streamDownload($csv, $flush_threshold=1000)
    {
        $content_callback = function () use ($csv, $flush_threshold) {
            foreach ($csv->chunk(1024) as $offset => $chunk) {
                echo $chunk;
                if ($offset % $flush_threshold === 0) {
                    flush();
                }
            }
        };

        $response = new StreamedResponse();
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-Type', 'text/csv; charset=utf8');
        $csv->setOutputBOM(Writer::BOM_UTF8);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'data.csv'
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Description', 'Csv Download');
        $response->setCallback($content_callback);
        return $response;
    }
}