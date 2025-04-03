<?php

namespace App\Http\Controllers;

use App\Models\Lotacao;
use App\Models\Pessoa;
use App\Models\Unidade;
use App\Models\ServidorEfetivo;
use App\Models\ServidorTemporario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LotacaoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $query = Lotacao::with(['pessoa', 'unidade']);
        
        if ($request->has('pessoa_nome')) {
            $nome = $request->input('pessoa_nome');
            $query->whereHas('pessoa', function($q) use ($nome) {
                $q->where('pes_nome', 'ILIKE', "%{$nome}%");
            });
        }
        
        if ($request->has('unidade_id')) {
            $unidadeId = $request->input('unidade_id');
            $query->where('unid_id', $unidadeId);
        }
        
        if ($request->has('ativas')) {
            if ($request->input('ativas') === 'true') {
                $query->where(function($q) {
                    $q->whereNull('lot_data_remocao')
                      ->orWhere('lot_data_remocao', '>=', now());
                });
            }
        }
        
        $lotacoes = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($lotacoes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pes_id' => 'required|exists:pessoas,pes_id',
            'unid_id' => 'required|exists:unidades,unid_id',
            'lot_data_lotacao' => 'required|date',
            'lot_data_remocao' => 'nullable|date|after_or_equal:lot_data_lotacao',
            'lot_portaria' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar se a pessoa é um servidor (efetivo ou temporário)
        $isServidor = ServidorEfetivo::where('pes_id', $request->pes_id)->exists() || 
                      ServidorTemporario::where('pes_id', $request->pes_id)->exists();
        
        if (!$isServidor) {
            return response()->json([
                'message' => 'A pessoa informada não é um servidor (efetivo ou temporário)'
            ], 400);
        }

        // Verificar se já existe uma lotação ativa para o servidor
        $lotacaoAtiva = Lotacao::where('pes_id', $request->pes_id)
            ->where(function($query) {
                $query->whereNull('lot_data_remocao')
                      ->orWhere('lot_data_remocao', '>=', now());
            })
            ->exists();
        
        if ($lotacaoAtiva) {
            return response()->json([
                'message' => 'Já existe uma lotação ativa para este servidor'
            ], 400);
        }

        try {
            $lotacao = Lotacao::create([
                'pes_id' => $request->pes_id,
                'unid_id' => $request->unid_id,
                'lot_data_lotacao' => $request->lot_data_lotacao,
                'lot_data_remocao' => $request->lot_data_remocao,
                'lot_portaria' => $request->lot_portaria,
            ]);
            
            return response()->json([
                'message' => 'Lotação criada com sucesso',
                'data' => $lotacao->load(['pessoa', 'unidade'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar lotação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $lotacao = Lotacao::with(['pessoa', 'unidade'])->find($id);
        
        if (!$lotacao) {
            return response()->json(['message' => 'Lotação não encontrada'], 404);
        }
        
        return response()->json($lotacao);
    }

    public function update(Request $request, $id)
    {
        $lotacao = Lotacao::find($id);
        
        if (!$lotacao) {
            return response()->json(['message' => 'Lotação não encontrada'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unid_id' => 'required|exists:unidades,unid_id',
            'lot_data_lotacao' => 'required|date',
            'lot_data_remocao' => 'nullable|date|after_or_equal:lot_data_lotacao',
            'lot_portaria' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $lotacao->update([
                'unid_id' => $request->unid_id,
                'lot_data_lotacao' => $request->lot_data_lotacao,
                'lot_data_remocao' => $request->lot_data_remocao,
                'lot_portaria' => $request->lot_portaria,
            ]);
            
            return response()->json([
                'message' => 'Lotação atualizada com sucesso',
                'data' => $lotacao->load(['pessoa', 'unidade'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar lotação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $lotacao = Lotacao::find($id);
        
        if (!$lotacao) {
            return response()->json(['message' => 'Lotação não encontrada'], 404);
        }
        
        try {
            $lotacao->delete();
            
            return response()->json(['message' => 'Lotação excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir lotação',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
