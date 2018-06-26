<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Storage;

class WxImageUploadHandler
{
    // 只允许以下后缀名的图片文件上传
    protected $allowed_ext = ["png", "jpg", "gif", 'jpeg'];

    public function save($file, $folder, $file_prefix)
    {
      $Path = "/uploads/$folder/";
      if (!empty($_FILES['file'])) {
          //获取扩展名
          $pathinfo = pathinfo($file['name']);
          $exename = strtolower($pathinfo['extension']);

          if (!in_array($exename, $this->allowed_ext)) {
              return false;
          }
          $fileName = $_SERVER['DOCUMENT_ROOT'] . $Path . date('Ym');//文件路径
          $upload_name = '/img_' . date("YmdHis") . rand(0, 100) . '.' . $exename;//文件名加后缀
          if (!file_exists($fileName)) {
              //进行文件创建
              mkdir($fileName, 0777, true);
          }
          $imageSavePath = $fileName . $upload_name;
          if (move_uploaded_file($file['tmp_name'], $imageSavePath)) {
              return date('Ym') . $upload_name;
          }
          return false;
      }
	}
}
