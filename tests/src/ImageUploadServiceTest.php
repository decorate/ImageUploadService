<?php
    namespace Test;

    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Storage;
    use Decorate\Services\ImageUploadService;
    use Decorate\Facades\ImageUpload;

    class ImageUploadServiceTest extends TestCase {

        private $dir = 'test';

        private $directories = ['large', 'normal', 'thumb'];

        private $storePath = '';

        protected function setUp(): void
        {
            parent::setUp();


            if(env('STORAGE_DISK') !== 's3') {
                $d = env('APP_URL');
                $d .= DIRECTORY_SEPARATOR. 'storage';
            } else {
                $d = 'https://'. env('AWS_BUCKET') . '.s3.';
                $d .= env('AWS_DEFAULT_REGION');
                $d .= '.amazonaws.com';
            }

            $this->storePath = $d;
        }

        /**
         * @group img
         */
        function testSaveMultiImageOK() {
            Storage::fake('test');

            $file = UploadedFile::fake()->image('t.jpg', '3500', '3500');

            $actual = ImageUpload::saveMultiImage($file, $this->dir);

            collect($this->directories)
                ->each(function ($x, $i) use($actual){
                    $url = $this->replaceDir($actual, $x);
                    ImageUpload::getStorage()->assertExists($this->getPath($url));

                    $image = \Image::make(ImageUpload::getStorage()->get($this->getPath($url)));

                    $assert = ImageUploadService::SIZES[$i];
                    $this->assertTrue($image->getWidth() === $assert);
                });
        }

        /**
         * 規定のサイズであれば、リサイズ、見たなければそのままのサイズで保存
         */
        function testSaveMultiImageSizeVerification() {
            Storage::fake('test');

            $file = UploadedFile::fake()->image('t.jpg', '1000');
            $res = ImageUpload::saveMultiImage($file, $this->dir);

            collect($this->directories)
                ->each(function ($x, $i) use($res){
                    $url = $this->replaceDir($res, $x);
                    $width = ImageUploadService::SIZES[$i];

                    $image = \Image::make(ImageUpload::getStorage()->get($this->getPath($url)));

                    if($x === 'large') {
                        $this->assertTrue($image->getWidth() === 1000);
                    } else {
                        $this->assertTrue($image->getWidth() === $width);
                    }

                });

        }


        private function getNormalTestPath($size = 'normal') {
            return $this->storePath. '/'. $this->dir. "/${size}/t.jpg";
        }

        private function replaceDir($url, $size = 'normal', $default = 'normal') {
            $s = str_replace('/storage', '', $url);
            return str_replace($default, $size, $s);
        }

        private function getPath($url) {
            return str_replace($this->storePath, '', $url);
        }

        protected function tearDown(): void
        {
            parent::tearDown();

            \ImageUpload::deleteDirectory($this->dir);
        }

    }