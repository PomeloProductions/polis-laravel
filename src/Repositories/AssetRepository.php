<?php

declare(strict_types=1);

namespace Polis\Repositories;

use App\Models\Asset;
use DomainException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Imagick;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Contracts\Services\Asset\AssetConfigurationServiceContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\Traits\NotImplemented;
use Polis\Traits\CanGetAndUnset;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class AssetRepository
 */
class AssetRepository extends BaseRepositoryAbstract implements AssetRepositoryContract
{
    use CanGetAndUnset, NotImplemented\FindOrFail;

    private Filesystem $publicAssets;

    private string $assetBaseURL;

    private string $basePublicDirectory;

    /**
     * AssetRepository constructor.
     */
    public function __construct(
        Asset $model,
        LogContract $log,
        Factory $fileSystem,
        AssetConfigurationServiceContract $assetConfigurationService,
    ) {
        parent::__construct($model, $log);
        $this->publicAssets = $fileSystem->disk('public');
        $this->assetBaseURL = $assetConfigurationService->getServerUrl();
        $this->basePublicDirectory = $assetConfigurationService->getBaseAssetDirectory();
    }

    /**
     * Overrides the parent create in order to process the file properly
     *
     * @return BaseModelAbstract
     *
     * @throws \ImagickException
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        if (isset($data['uploaded_file'])) {
            /** @var UploadedFile $file */
            $file = $data['uploaded_file'];
            $file->storePubliclyAs('public/'.$this->basePublicDirectory, $file->getClientOriginalName());
            $data['url'] = $this->assetBaseURL.'/'.$this->basePublicDirectory.'/'.$file->getClientOriginalName();

            unset($data['uploaded_file']);
        } else {

            $fileContents = $this->getAndUnset($data, 'file_contents');
            $fileExtension = $this->getAndUnset($data, 'file_extension');

            if ($fileContents && $fileExtension) {
                $fileInfo = $this->storeFile($fileContents, $fileExtension);
                $data['url'] = $this->assetBaseURL.'/'.$fileInfo['file_name'];
                $data['width'] = $fileInfo['width'] ?? null;
                $data['height'] = $fileInfo['height'] ?? null;
            }
        }

        return parent::create($data, $relatedModel, $forcedValues);
    }

    /**
     * Generates a public file name for an asset
     */
    protected function generatePublicFileName($fileExtension): string
    {
        $attempts = 0;

        do {
            if ($attempts == 5) {
                throw new DomainException('Unable to generate a proper file name for the public file.');
            }
            $attempts++;

            $imageName = $this->basePublicDirectory.'/'.Str::random(40).'.'.$fileExtension;
        } while ($this->publicAssets->exists($imageName));

        return $imageName;
    }

    /**
     * Store an uploaded image and return the path
     *
     * @throws \ImagickException
     */
    protected function storeFile($fileContents, $fileExtension): array
    {
        $attempts = 0;

        $data = [];

        do {
            if ($attempts == 5) {
                throw new DomainException('Unable to generate a proper file name for the public file.');
            }
            $fileName = $this->generatePublicFileName($fileExtension);
            $attempts++;

        } while ($this->publicAssets->exists($fileName));

        if (in_array(Str::lower($fileExtension), ['png', 'jpg', 'gif', 'jpeg'])) {
            $image = new Imagick;
            $image->readImageBlob($fileContents);
            $image->setImageFormat($fileExtension);

            $orientation = $image->getImageOrientation();

            switch ($orientation) {
                case Imagick::ORIENTATION_BOTTOMRIGHT:
                    $image->rotateimage('#000', 180); // rotate 180 degrees
                    break;

                case Imagick::ORIENTATION_RIGHTTOP:
                    $image->rotateimage('#000', 90); // rotate 90 degrees CW
                    break;

                case Imagick::ORIENTATION_LEFTBOTTOM:
                    $image->rotateimage('#000', -90); // rotate 90 degrees CCW
                    break;
            }

            $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);

            $image->commentImage('Uploaded to CNH');

            $fileContents = $image->__toString();

            $data['width'] = $image->getImageWidth();
            $data['height'] = $image->getImageHeight();
        }

        $this->publicAssets->put($fileName, $fileContents);

        $data['file_name'] = $fileName;

        return $data;
    }
}
