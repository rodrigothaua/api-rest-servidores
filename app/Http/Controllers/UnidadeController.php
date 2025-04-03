<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use App\Models\Endereco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnidadeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $query = Unidade::with(['enderecos', 'enderecos.cidade']);
        
        if ($request->has('nome')) {
            $nome = $request->input('nome');
            $query->where('unid_nome', 'ILIKE', "%{$nome}%");
        }
        
        if ($request->has('sigla')) {
            $sigla = $request->input('sigla');
            $query->where('unid_sigla', 'ILIKE', "%{$sigla}%");
        }
        
        $unidades = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($unidades);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unid_nome' => 'required|string|max:200',
            'unid_sigla' => 'nullable|string|max:20',
            'enderecos' => 'nullable|array',
            'enderecos.*.end_tipo_logradouro' => 'nullable|string|max:20',
            'enderecos.*.end_logradouro' => 'required|string|max:200',
            'enderecos.*.end_numero' => 'nullable|integer',
            'enderecos.*.end_bairro' => 'nullable|string|max:100',
            'enderecos.*.cid_id' => 'required|exists:cidades,cid_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Criar unidade
            $unidade = Unidade::create([
                'unid_nome' => $request->unid_nome,
                'unid_sigla' => $request->unid_sigla,
            ]);

            // Adicionar endereços se fornecidos
            if ($request->has('enderecos')) {
                foreach ($request->enderecos as $enderecoData) {
                    $endereco = Endereco::create([
                        'end_tipo_logradouro' => $enderecoData['end_tipo_logradouro'] ?? null,
                        'end_logradouro' => $enderecoData['end_logradouro'],
                        'end_numero' => $enderecoData['end_numero'] ?? null,
                        'end_bairro' => $enderecoData['end_bairro'] ?? null,
                        'cid_id' => $enderecoData['cid_id'],
                    ]);
                    
                    $unidade->enderecos()->attach($endereco->end_id);
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Unidade criada com sucesso',
                'data' => $unidade->load(['enderecos', 'enderecos.cidade'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao criar unidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $unidade = Unidade::with(['enderecos', 'enderecos.cidade'])->find($id);
        
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }
        
        return response()->json($unidade);
    }

    public function update(Request $request, $id)
    {
        $unidade = Unidade::find($id);
        
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'unid_nome' => 'required|string|max:200',
            'unid_sigla' => 'nullable|string|max:20',
            'enderecos' => 'nullable|array',
            'enderecos.*.end_tipo_logradouro' => 'nullable|string|max:20',
            'enderecos.*.end_logradouro' => 'required|string|max:200',
            'enderecos.*.end_numero' => 'nullable|integer',
            'enderecos.*.end_bairro' => 'nullable|string|max:100',
            'enderecos.*.cid_id' => 'required|exists:cidades,cid_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Atualizar unidade
            $unidade->update([
                'unid_nome' => $request->unid_nome,
                'unid_sigla' => $request->unid_sigla,
            ]);

            // Atualizar endereços se fornecidos
            if ($request->has('enderecos')) {
                $unidade->enderecos()->detach();

                foreach ($request->enderecos as $enderecoData) {
                    $endereco = Endereco::create([
                        'end_tipo_logradouro' => $enderecoData['end_tipo_logradouro'] ?? null,
                        'end_logradouro' => $enderecoData['end_logradouro'],
                        'end_numero' => $enderecoData['end_numero'] ?? null,
                        'end_bairro' => $enderecoData['end_bairro'] ?? null,
                        'cid_id' => $enderecoData['cid_id'],
                    ]);
                    
                    $unidade->enderecos()->attach($endereco->end_id);
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Unidade atualizada com sucesso',
                'data' => $unidade->load(['enderecos', 'enderecos.cidade'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao atualizar unidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $unidade = Unidade::find($id);
        
        if (!$unidade) {
            return response()->json(['message' => 'Unidade não encontrada'], 404);
        }

        DB::beginTransaction();
        try {
            // Remover endereços associados
            $unidade->enderecos()->detach();

            // Deletar unidade
            $unidade->delete();

            DB::commit();
            
            return response()->json(['message' => 'Unidade deletada com sucesso'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao deletar unidade',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
