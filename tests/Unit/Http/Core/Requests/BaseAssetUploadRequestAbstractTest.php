<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Requests;

use Polis\Tests\Mocks\AssetUploadRequest;
use Polis\Tests\TestCase;
use RuntimeException;

/**
 * Class BaseAssetUploadRequestAbstractTest
 */
final class BaseAssetUploadRequestAbstractTest extends TestCase
{
    public function test_validation_data_sets_mime_type(): void
    {
        $request = new AssetUploadRequest;

        $request->replace([
            'file_contents' => base64_encode('test'),
        ]);

        $data = callMethod($request, 'validationData');

        $this->assertEquals($data['mime_type'], 'text/plain');
    }

    public function test_get_decoded_contents_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $request = new AssetUploadRequest;

        $request->getDecodedContents();
    }

    public function test_get_decoded_contents_returns_correct_contents(): void
    {
        $request = new AssetUploadRequest;

        $request->replace([
            'file_contents' => base64_encode('<svg></svg>'),
        ]);

        callMethod($request, 'validationData');

        $this->assertEquals('<svg></svg>', $request->getDecodedContents());
    }

    public function test_get_file_mime_type_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $request = new AssetUploadRequest;

        $request->getFileMimeType();
    }

    public function test_get_file_mime_type_returns_correct_contents(): void
    {
        $request = new AssetUploadRequest;

        $request->replace([
            'file_contents' => base64_encode('<svg></svg>'),
        ]);

        callMethod($request, 'validationData');

        $this->assertEquals('image/svg+xml', $request->getFileMimeType());
    }
}
