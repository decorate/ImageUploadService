<?php
    namespace Test;

    class TestCase extends \Orchestra\Testbench\TestCase {

        protected function getPackageProviders ($app)
        {
            return [
                'Sh\Providers\ImageUploadProvider',
                'Illuminate\Filesystem\FilesystemServiceProvider',
                'Intervention\Image\ImageServiceProvider'
            ];
        }

        protected function getPackageAliases ($app)
        {
            return [
              'ImageUpload' => 'Sh\Facades\ImageUpload',
              'Storage' => 'Illuminate\Support\Facades\Storage',
              'Image' => 'Intervention\Image\Facades\Image'
            ];
        }
    }