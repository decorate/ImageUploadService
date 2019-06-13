<?php

namespace Sh\Services;


use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageUploadService
{

    private $faker;
    private $storage;

    const KILOBYTE_8 = 1024 * 8;

    const SIZE_THUMB = 'thumb';
    const SIZE_NORMAL = 'normal';
    const SIZE_LARGE = 'large';

    const SIZE_NAMES = [
        self::SIZE_THUMB,
        self::SIZE_NORMAL,
        self::SIZE_LARGE,
    ];

    const THUMB_WIDTH = 300;
    const NORMAL_WIDTH = 800;
    const LARGE_WIDTH = 1500;

    const SIZES = [
        self::LARGE_WIDTH,
        self::NORMAL_WIDTH,
        self::THUMB_WIDTH,
    ];

    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
        $this->storage = Storage::disk(env('STORAGE_DISK', 'public'));
    }

    public function upload ($image, string $key, $option = 'public') {

        if ($this->isBase64($image)) {
            return $this->uploadFromBase64($image, $key, $option);
        }

        if (Validator::make(['url' => $image], ['url' => 'url'])->passes()) {
            return $image;
        }

        if (Validator::make(['file' => $image], ['file' => 'file'])->passes()) {
            return $this->uploadFile($image, $key, $option);
        }

        if (Validator::make(['path' => $image], ['regex' => '/^(.*/).*$/'])->passes()) {
            return $image;
        }

    }

    private function uploadFile ($file, string $key, $option): string {
        $path = $this->storage->putFile($key, $file, $option);
        return $this->storage->url($path);
    }

    /**
     * @param $image
     * @param string $key
     * @return string
     */
    private function uploadFromBase64 ($image, string $key, $option): string {
        $ext = $this->getExtensionFromBase64($image);
        $image = explode(',', $image);

        $tmp = base64_decode(end($image));
        $path = $key. $this->faker->uuid. '.'. $ext;
        $this->storage->put($path, $tmp, $option);
        $url = $this->storage->url($path);

        return $url;
    }

    /**
     * @param $image
     * @return UploadedFile
     * /storage/app/public/tmp
     * 内にファイルを保存する
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createUploadFile($image) {
        $url = $this->uploadFromBase64($image, '/tmp/', 'public');
        $basename = basename($url, PATHINFO_BASENAME);
        $path = storage_path('app/public/tmp/'. $basename);
        $u = new UploadedFile($path, $basename);

        \Validator::validate(['size' => $u->getSize()/1024], ['size' => 'lte:'. self::KILOBYTE_8]);
        return $u;
    }

    public function getStorage() {
        return $this->storage;
    }

    public function getExtensionFromBase64 ($file) {
        preg_match('/gif|png|jpg|jpeg|bmp/i', $file, $res);

        if (!isset($res[0])) {
            return false;
        }

        return $res[0];
    }

    public function isBase64 ($image): bool {
        return str_contains($image, 'data:image/');
    }

    public function saveMultiImage (UploadedFile $file, $saveDir, $returnDir = self::SIZE_NORMAL) {
        $dir = $this->buildDir($saveDir);

        $normalPath = '';

        $check = $this->storage
            ->exists($this->getPath($file, $dir, $returnDir));
        if ($check) {
            return $this->storage->url($this->getPath($file, $dir, $returnDir));
        }


        collect(self::SIZE_NAMES)
            ->each(function ($x) use ($file, $dir, &$normalPath, $returnDir) {
                $path = $this->getPath($file, $dir, $x);

                $content = \Image::make($file->getPathname());
                $width = $content->getWidth();

                if ($x === self::SIZE_THUMB && $width >= self::THUMB_WIDTH) {
                    $content->widen(self::THUMB_WIDTH);
                }

                if ($x === self::SIZE_NORMAL && $width >= self::NORMAL_WIDTH) {
                    $content->widen(self::NORMAL_WIDTH);
                }

                if ($x === self::SIZE_LARGE && $width >= self::LARGE_WIDTH) {
                    $content->widen(self::LARGE_WIDTH);
                }

                if ($x === $returnDir) {
                    $normalPath = $path;
                }

                $image = $content->orientate()->encode('jpg')->getEncoded();
                $content->destroy();
                $this->storage->put($path, $image, 'public');

            });

        \File::delete($file->getPathname());

        return $this->storage->url($normalPath);
    }

    public function deleteMultiImage (UploadedFile $file, $saveDir) {
        $dir = $this->buildDir($saveDir);

        collect(self::SIZE_NAMES)
            ->each(function ($x) use ($file, $dir){
                $path = $this->getPath($file, $dir, $x);

                $this->delete($path);
            })
        ;
    }

    public function delete ($path) {
        return $this->storage->delete($path);
    }

    /**
     * @param $path
     * @return bool
     */
    public function deleteDirectory($path) {
        return $this->storage->deleteDirectory($path);
    }

    private function getPath (UploadedFile $file, $dir, $size) {
        return "{$dir}/{$size}/{$file->getClientOriginalName()}";
    }

    private function buildDir($dir) {
        $patterns = [
            "/\/?$/",
            "/^\/*/"
        ];

        return preg_replace($patterns, '', $dir);
    }

    /**
     * @param $model
     * @param array $images
     * @return Collection
     */
    public function createImageModel($model, array $images) {
        return collect($images)
            ->filter(function($x) {
                return !isset($x['id']);
            })
            ->map(function($x) use($model){
                return new $model($x);
            });
    }

}
