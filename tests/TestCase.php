<?php
    namespace Test;

    class TestCase extends \Orchestra\Testbench\TestCase {

        protected function getPackageProviders ($app)
        {
            return [
                'Decorate\Providers\ImageUploadProvider',
                'Illuminate\Filesystem\FilesystemServiceProvider',
                'Intervention\Image\ImageServiceProvider'
            ];
        }

        protected function getPackageAliases ($app)
        {
            return [
              'ImageUpload' => 'Decorate\Facades\ImageUpload',
              'Storage' => 'Illuminate\Support\Facades\Storage',
              'Image' => 'Intervention\Image\Facades\Image'
            ];
        }
    }