<?php
header('Content-Type: application/json');
$res = [
    'status' => 200,
    'msg'    => '上传成功',
    'path'   => '',
    'names'  => []
];

function mvFile($file, $isNeedName = false)
{
    $fileName = $file['name']; // 获取文件
    $fileExtension = pathinfo($fileName);  // 获取文件路径信息
    $fileExtension = $fileExtension['extension']; // 获取文件后缀
    $time = time(); // 根据时间戳区分
    $destinationPath = "upload/";  // 目标文件夹
    $fn = $time . "." . $fileExtension;
    $newFileName = $destinationPath . $fn;
    // 完整的url
    if (rename($file['tmp_name'], $newFileName)) {       // 移动文件到目标路径
        if (!$isNeedName) {
            return $newFileName;
        } else {
            return $fn;
        }
    } else {
        return false;
    }
}

if (!empty($_FILES)) {
    $file = $_FILES['file'];
    if (isset($file['name'])) {// 文件存在
        if (is_array($file['name'])) {
            foreach ($file['name'] as $k => $fn) {
                if (!$file['error'][$k]) {
                    $fileBuf = [
                        'name'     => $fn,
                        'tmp_name' => $file['tmp_name'][$k],
                    ];
                    if ($path = mvFile($fileBuf, true)) {
                        $res['names'][] = $path;
                    } else {
                        die("文件路径出错");
                    }
                }
            }
        } else if (!$file['error']) {
            if ($path = mvFile($file)) {
                $res['path'] = '../'.$path;
            } else {
                die("文件路径出错");
            }
        }
    }
}

echo json_encode($res);