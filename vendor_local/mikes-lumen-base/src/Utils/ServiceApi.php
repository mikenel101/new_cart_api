<?php

namespace MikesLumenBase\Utils;

class ServiceApi
{
    public function getStorageDirectory($path)
    {
        return app('fetcher')->fetch('GET', getenv('API_MEDIA_ENDPOINT'), 'files', ['path' => $path]);
    }

    public function addImage($image, $path, $sizes = ['image'], $method = null)
    {
        $data = [
            'image' => $image,
            'path' => $path
        ];
        if ($method) {
            $data['method'] = $method;
        }
        for ($i = 0; $i < count($sizes); $i++) {
            $data['sizes[' . $i . ']'] = $sizes[$i];
        }

        return app('fetcher')->fetch('POST', getenv('API_MEDIA_ENDPOINT'), 'images', $data, [], true);
    }

    public function addFiles($file, $path, $fileName, $extension)
    {
        $data = [
            'file' => $file,
            'path' => $path,
            'file_name' => $fileName,
            'extension' => $extension
        ];

        return app('fetcher')->fetch('POST', getenv('API_MEDIA_ENDPOINT'), 'files', $data, [], true);
    }

    public function deleteFiles($path)
    {
        return app('fetcher')->fetch('DELETE', getenv('API_MEDIA_ENDPOINT'), 'files', ['path' => $path]);
    }

    /**
     * Send Email
     *
     * @param  string $templateCode
     * @param  string $subject
     * @param  string $to
     * @param  array $headers
     * @param  array $variables
     * @return array|false
     */
    public function sendEmail(string $templateCode, string $subject, string $to, array $headers, array $variables = [])
    {
        $data = [
            'template_code' => $templateCode,
            'subject'       => $subject,
            'to'            => $to,
            'variables'     => $variables,
        ];

        return app('fetcher')->fetch('POST', getenv('API_MAIL_ENDPOINT'), 'mails/send', $data, $headers);
    }
}
