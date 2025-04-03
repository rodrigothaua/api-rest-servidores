<?php

namespace App\Http\Controllers;

use App\Models\FotoPessoa;
use App\Models\Pessoa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class FotoController extends Controller
{
    public function upload(Request $request, $pesId)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'required|image|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pessoa = Pessoa::find($pesId);
        
        if (!$pessoa) {
            return response()->json(['message' => 'Pessoa não encontrada'], 404);
        }

        try {
            $file = $request->file('foto');
            $hash = Str::random(40);
            $extension = $file->getClientOriginalExtension();
            $filename = $hash . '.' . $extension;
            
            // Upload para o MinIO
            Storage::disk('s3')->put($filename, file_get_contents($file));
            
            // Salvar informações no banco
            $fotoPessoa = FotoPessoa::create([
                'pes_id' => $pesId,
                'fp_data' => now(),
                'fp_bucket' => env('MINIO_BUCKET'),
                'fp_hash' => $hash,
            ]);
            
            return response()->json([
                'message' => 'Foto enviada com sucesso',
                'data' => $fotoPessoa
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTemporaryUrl($hash)
    {
        try {
            $foto = FotoPessoa::where('fp_hash', $hash)->first();
            
            if (!$foto) {
                return null;
            }
            
            $files = Storage::disk('s3')->files();
            $filename = null;
            
            foreach ($files as $file) {
                if (strpos($file, $hash) === 0) {
                    $filename = $file;
                    break;
                }
            }
            
            if (!$filename) {
                return null;
            }
            
            // Gerar URL temporária válida por 5 minutos
            return Storage::disk('s3')->temporaryUrl($filename, now()->addMinutes(5));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getFoto($hash)
    {
        try {
            $url = $this->getTemporaryUrl($hash);
            
            if (!$url) {
                return response()->json(['message' => 'Foto não encontrada'], 404);
            }
            
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteFoto($id)
    {
        $foto = FotoPessoa::find($id);
        
        if (!$foto) {
            return response()->json(['message' => 'Foto não encontrada'], 404);
        }
        
        try {
            $hash = $foto->fp_hash;
            $files = Storage::disk('s3')->files();
            $filename = null;
            
            foreach ($files as $file) {
                if (strpos($file, $hash) === 0) {
                    $filename = $file;
                    break;
                }
            }
            
            if ($filename) {
                Storage::disk('s3')->delete($filename);
            }
            
            $foto->delete();
            
            return response()->json(['message' => 'Foto excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
