<?php

namespace LgtToolkit\Ajax;

use Core;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @deprecated
 */
class PlaceholderText
{
    protected array $options = [
        'cookie',
        'privacy',
        'accessibility',
    ];

    public function getDummyText(): Response
    {
        $token = Core::make('token');
        $fh = Core::make('helper/file');
        $pkg = Core::make('Concrete\Core\Package\PackageService')->getByHandle('lgt-toolkit');

        $code = 200;
        $response = [
            'request' => $_REQUEST,
        ];

        if ($token->validate('lgt_content_site_attribute')) {
            $dummy_handle = $_REQUEST['dummy'];

            if (in_array($dummy_handle, $this->options)) {
                $dummyPath = sprintf('%s/blocks/lgt_content_site_attribute/dummy/%s.txt', $pkg->getPackagePath(), $dummy_handle);
                $contents = $fh->getContents($dummyPath);

                if ($contents) {
                    $response['content'] = $contents;
                } else {
                    $code = 404;
                    $response['error'] = t('Could not open file');
                }
            } else {
                $code = 400;
                $response['error'] = t('Invalid Option selected');
            }
        } else {
            $code = 400;
            $response['error'] = $token->getErrorMessage();
        }

        return new JsonResponse($response, $code);
    }
}
