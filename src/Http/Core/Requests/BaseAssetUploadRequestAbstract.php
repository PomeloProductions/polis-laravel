<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests;

use Polis\Http\Core\Requests\Traits\HasNoExpands;
use RuntimeException;

/**
 * Class BaseAssetUploadRequestAbstract
 */
abstract class BaseAssetUploadRequestAbstract extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * @var string|null
     */
    private $decodedContents = null;

    /**
     * @var string|null
     */
    private $fileMimeType = null;

    /**
     * Attempts to decode the file, and set relevant information
     *
     * @return array
     */
    public function validationData()
    {
        $data = parent::validationData();

        if (isset($data['file_contents']) && is_string($data['file_contents']) && $this->decodedContents === null) {

            $this->decodedContents = base64_decode($data['file_contents']);

            if ($this->decodedContents) {

                $f = finfo_open();
                $this->fileMimeType = finfo_buffer($f, $this->decodedContents, FILEINFO_MIME_TYPE);
            }
        }

        $data['mime_type'] = $this->fileMimeType;

        return $data;
    }

    /**
     * returns the decoded file contents, or throws an exception in an invalid state
     *
     * @return string
     */
    public function getDecodedContents()
    {
        if ($this->decodedContents) {
            return $this->decodedContents;
        }

        throw new RuntimeException('Decoded contents attempted to be retrieved before request validation');
    }

    /**
     * returns the file mime type, or throws an exception in an invalid state
     *
     * @return string
     */
    public function getFileMimeType()
    {
        if ($this->fileMimeType) {
            return $this->fileMimeType;
        }

        throw new RuntimeException('File mime type attempted to be retrieved before request validation');
    }
}
