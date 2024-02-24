<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccessRequest;
use App\Http\Requests\FileChangeRequest;
use Illuminate\Http\Request;
use App\Models\File;
use App\Models\Access;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
  public function add(Request $request)
  {
    $host = "http://laravel/";

    $user = auth()->user();
    $files = $request->allFiles();

    foreach ($files as $value) {

      $name = $value->getClientOriginalName();
      $fileSize = $value->getSize();

      $fileName = pathinfo($name, PATHINFO_FILENAME);
      $fileExtension = pathinfo($name, PATHINFO_EXTENSION);

      if (
        $fileSize > 1024 * 2
        && ($fileExtension == "doc"
          || $fileExtension == "pdf"
          || $fileExtension == "docx"
          || $fileExtension == "zip"
          || $fileExtension == "jpeg"
          || $fileExtension == "jpg"
          || $fileExtension == "jpg"
        )
      ) {
        $originalFileName = $fileName;
        for ($i = 1; ; $i++) {
          $fileСompare = File::all()
            ->where('name', ($fileName . '.' . $fileExtension))
            ->where('user_id', $user->id)
            ->first();
          if ($fileСompare) {
            $fileName = $originalFileName . " ($i)";
          } else {
            break;
          }
        }

        for (; ; ) {
          $randomName = Str::random(10);
          $fileСompare = File::all()
            ->where('pash', ("file/" . $randomName . '.' . $fileExtension))
            ->first();
          if (!$fileСompare) {
            break;
          }
        }

        $pash = $value->storeAs('file', $randomName . '.' . $fileExtension);

        $file = new File();
        $file->user_id = $user->id;
        $file->pash = $pash;
        $file->name = ($fileName . '.' . $fileExtension);
        $file->save();

        $res[] = [
          "success" => true,
          "message" => "Success",
          "name" => ($fileName . '.' . $fileExtension),
          "url" => $host . "files/" . $name,
          "file_id" => $randomName,
        ];
      } else {
        $res[] = [
          "success" => false,
          "message" => "File not loaded",
          "name" => $name,
          "url" => $host . "files/" . $name
        ];
      }
    }
    return response($res);
  }
  public function change($file_id, FileChangeRequest $request)
  {
    $user = auth()->user();
    $file = File::where('pash', 'like', ("file/" . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        "success" => false,
        "message" => "Not found",
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        "success" => false,
        "message" => "Forbidden for you",
      ], 401);
    }

    $fileName = $request->name;
    $fileExtension = pathinfo($file->name, PATHINFO_EXTENSION);

    $originalFileName = $fileName;
    for ($i = 1; ; $i++) {
      $fileСompare = File::all()
        ->where('name', ($fileName . '.' . $fileExtension))
        ->where('user_id', $user->id)
        ->first();
      if ($fileСompare) {
        $fileName = $originalFileName . " ($i)";
      } else {
        break;
      }
    }

    $file->name = ($fileName . "." . $fileExtension);
    $file->save();

    return response([
      "success" => true,
      "message" => "Renamed",
    ]);
  }
  public function delete($file_id, Request $request)
  {
    $user = auth()->user();
    $file = File::where('pash', 'like', ("file/" . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        "success" => false,
        "message" => "Not found",
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        "success" => false,
        "message" => "Forbidden for you",
      ], 401);
    }

    Storage::delete($file->pash);
    $file->delete();

    return response([
      "success" => true,
      "message" => "File already deleted",
    ]);
  }

  public function get($file_id, Request $request)
  {
    $user = auth()->user();
    $file = File::where('pash', 'like', ("file/" . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        "success" => false,
        "message" => "Not found",
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        "success" => false,
        "message" => "Forbidden for you",
      ], 401);
    }

    return Storage::download($file->pash);
  }

  public function setAccess($file_id, AccessRequest $request)
  {
    $user = auth()->user();
    $file = File::where('pash', 'like', ("file/" . $file_id . "%"))
      ->first();
    if (!$file) {
      return response([
        "success" => false,
        "message" => "Not found",
      ], 404);
    }
    if ($file->user_id != $user->id) {
      return response([
        "success" => false,
        "message" => "Forbidden for you",
      ], 401);
    }

    $coAutor = Access::where('user_id', $file->id)
      ->where('file_id', $file->id)
      ->first();
    if (!$coAutor) {
      $coAutor = User::where('email', $request->email)
        ->first();

      $access = new Access();
      $access->file_id = $file->id;
      $access->user_id = $coAutor->id;
      $access->save();
    }

    $coAutors = Access::where('file_id', $file->id)
      ->with('user')
      ->get();

    $res[] = [
      "fullname" => ($user->first_name . " " . $user->last_name),
      "email" => $user->email,
      "type" => "author",
    ];

    foreach ($coAutors as $coAutor) {
      $res[] = [
        "fullname" => ($coAutor->user->first_name . " " . $coAutor->user->last_name),
        "email" => $coAutor->user->email,
        "type" => "co-author",
      ];
    }

    return response($res);
  }
}
