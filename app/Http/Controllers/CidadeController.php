<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use Illuminate\Http\Request;

class CidadeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $query = Cidade::query();
        
        if ($request->has('nome')) {
            $nome = $request->input('nome');
            $query->where('cid_nome', 'ILIKE', "%{$nome}%");
        }
        
        if ($request->has('uf')) {
            $uf = $request->input('uf');
            $query->where('cid_uf', 'ILIKE', "%{$uf}%");
        }
        
        $cidades = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($cidades);
    }

    public function show($id)
    {
        $cidade = Cidade::find($id);
        
        if (!$cidade) {
            return response()->json(['message' => 'Cidade não encontrada'], 404);
        }
        
        return response()->json($cidade);
    }
}
