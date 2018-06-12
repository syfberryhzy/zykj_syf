<?php

namespace App\Handlers;

use Illuminate\Support\Facades\Storage;

class WxImageUploadHandler
{
    // 只允许以下后缀名的图片文件上传
    protected $allowed_ext = ["png", "jpg", "gif", 'jpeg'];

    public function save($file, $folder, $file_prefix)
    {
        $folder_name = "uploads/$folder/" . date("Ym/d", time());
        // $upload_path = public_path() . '/' . $folder_name;
        if (!empty($file)) {
            //获取扩展名
            $fileName = $file['name'];
            $pathinfo = pathinfo($fileName);
            $extension = strtolower($pathinfo['extension']);
            // 如果上传的不是图片将终止操作
            if ( ! in_array($extension, $this->allowed_ext)) {
                return false;
            }
            $data = $file['tmp_name'];
            // // 值如：1_1493521050_7BVc9v9ujP.png
            $file_name = $file_prefix . '_' . time() . '_' . str_random(10) . '.' . $extension;
              // $file_name = date('Y_m_d_') . uniqid() . '.png';

            // 将图片移动到我们的目标存储路径中
            $result = Storage::disk($folder)->put($file_name, $data);
            if($result) {
              return $file_name;
            }

        }
		 return false;
	}
}
