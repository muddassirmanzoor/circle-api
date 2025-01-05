<?php

namespace App\Helpers;

use Aws\S3\S3Client;
use Aws\CommandPool;

Class MediaUploadHelper {
    /*
      |--------------------------------------------------------------------------
      | MediaUploadHelper that contains media related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use media processes
      |
     */

    /**
     * Description of ActivityHelper
     *
     * @author ILSA Interactive
     */
    public static function getS3Object() {
        $s3Client = S3Client::factory(array(
                    'credentials' => [
                        'key' => config('paths.s3_access_key'),
                        'secret' => config('paths.s3_secret_key'),
                    ],
                    'region' => config('paths.s3_bucket_region'),
                    'version' => 'latest',
        ));
        return $s3Client;
    }

    public static function moveS3Videos($video_keys, $destination_slug = "uploads/general/") {
        $s3Client = self::getS3Object();
        $batch = [];
        if (!empty($video_keys)) {
            foreach ($video_keys as $media) {
                $copyModel = self::getVideoCopyModel($media['video'], $destination_slug);
//            $saveModel = self::getSaveModel($media);
                $batch[] = $s3Client->getCommand('CopyObject', $copyModel);
//            $batch[] = $s3Client->getCommand('GetObject', $saveModel);
            }
        }
        CommandPool::batch($s3Client, $batch);
    }

    public static function moveSingleS3Videos($video_key, $destination_slug = "uploads/general/") {
        $s3Client = self::getS3Object();
        $batch = [];
        if (!empty($video_key)) {
            $copyModel = self::getVideoCopyModel($video_key, $destination_slug);
//            $saveModel = self::getSaveModel($media);
            $batch[] = $s3Client->getCommand('CopyObject', $copyModel);
//            $batch[] = $s3Client->getCommand('GetObject', $saveModel);
        }
        CommandPool::batch($s3Client, $batch);
    }

    public static function moveS3Images($images_keys, $destination_slug = "uploads/general/") {
        $s3Client = self::getS3Object();
        $batch = [];
        if (!empty($images_keys)) {
            foreach ($images_keys as $media) {
                $copyModel = self::getImageCopyModel($media, $destination_slug);
//            $saveModel = self::getSaveModel($media);
                $batch[] = $s3Client->getCommand('CopyObject', $copyModel);
//            $batch[] = $s3Client->getCommand('GetObject', $saveModel);
            }
        }
        CommandPool::batch($s3Client, $batch);
    }

    public static function moveSingleS3Image($image_key, $destination_slug = "uploads/general/") {
        $s3Client = self::getS3Object();
        $batch = [];
        $copyModel = self::getImageCopyModel($image_key, $destination_slug);
//            $saveModel = self::getSaveModel($media);
        $batch[] = $s3Client->getCommand('CopyObject', $copyModel);
//            $batch[] = $s3Client->getCommand('GetObject', $saveModel);

        CommandPool::batch($s3Client, $batch);
    }

    public static function getImageCopyModel($media, $destination_slug, $copy_source = null) {
        if (empty($copy_source) || $copy_source == null) {
            $copy_source = CommonHelper::$s3_image_paths['mobile_uploads'];
        }
        $model = [
            'Bucket' => config('paths.s3_bucket'),
            'Key' => $destination_slug . $media,
            'CopySource' => config('paths.s3_bucket') . "/" . $copy_source . "{$media}",
        ];
        return $model;
    }

    public static function getVideoCopyModel($media, $destination_slug, $copy_source = null) {
        if (empty($copy_source) || $copy_source == null) {
            $copy_source = CommonHelper::$s3_image_paths['mobile_uploads'];
        }
        $model = [
            'Bucket' => config('paths.s3_bucket'),
            'Key' => $destination_slug . $media,
            'CopySource' => config('paths.s3_bucket') . "/" . $copy_source . "{$media}",
        ];
        return $model;
    }

    public static function getSaveModel($media, $copy_source = null) {
        if (empty($copy_source) || $copy_source == null) {
            $copy_source = CommonHelper::$s3_image_paths['mobile_uploads'];
        }
        $model = [
            'Bucket' => config('paths.s3_bucket'),
            'Key' => $copy_source . "{$media}",
            'SaveAs' => public_path() . "/uploads/{$media}"
        ];
        return $model;
    }

}

?>