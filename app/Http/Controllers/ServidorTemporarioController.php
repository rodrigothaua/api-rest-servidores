<?php

namespace App\Http\Controllers;

use App\Models\Pessoa;
use App\Models\ServidorTemporario;
use App\Models\Endereco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServidorTemporarioController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $query = ServidorTemporario::with(['pessoa', 'pessoa.fotos']);
        
        if ($request->has('nome')) {
            $nome = $request->input('nome');
            $query->whereHas('pessoa', function($q) use ($nome) {
                $q->where('pes_nome', 'ILIKE', "%{$nome}%");
            });
        }
        
        $servidores = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($servidores);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pes_nome' => 'required|string|max:200',
            'pes_data_nascimento' => 'nullable|date',
            'pes_sexo' => 'nullable|string|max:1',
            'pes_mae' => 'nullable|string|max:200',
            'pes_pai' => 'nullable|string|max:200',
            'st_data_admissao' => 'required|date',
            'st_data_demissao' => 'nullable|date|after_or_equal:st_data_admissao',
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
            // Criar pessoa
            $pessoa = Pessoa::create([
                'pes_nome' => $request->pes_nome,
                'pes_data_nascimento' => $request->pes_data_nascimento,
                'pes_sexo' => $request->pes_sexo,
                'pes_mae' => $request->pes_mae,
                'pes_pai' => $request->pes_pai,
            ]);

            // Criar servidor temporário
            $servidor = ServidorTemporario::create([
                'pes_id' => $pessoa->pes_id,
                'st_data_admissao' => $request->st_data_admissao,
                'st_data_demissao' => $request->st_data_demissao,
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
                    
                    $pessoa->enderecos()->attach($endereco->end_id);
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Servidor temporário criado com sucesso',
                'data' => $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.enderecos.cidade'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao criar servidor temporário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $servidor = ServidorTemporario::with([
            'pessoa', 
            'pessoa.enderecos', 
            'pessoa.enderecos.cidade', 
            'pessoa.fotos',
            'lotacoes',
            'lotacoes.unidade'
        ])->find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor temporário não encontrado'], 404);
        }
        
        return response()->json($servidor);
    }

    public function update(Request $request, $id)
    {
        $servidor = ServidorTemporario::find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor temporário não encontrado'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'pes_nome' => 'required|string|max:200',
            'pes_data_nascimento' => 'nullable|date',
            'pes_sexo' => 'nullable|string|max:1',
            'pes_mae' => 'nullable|string|max:200',
            'pes_pai' => 'nullable|string|max:200',
            'st_data_admissao' => 'required|date',
            'st_data_demissao' => 'nullable|date|after_or_equal:st_data_admissao',
            'enderecos' => 'nullable|array',
            'enderecos.*.end_id' => 'nullable|exists:enderecos,end_id',
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
            // Atualizar pessoa
            $pessoa = Pessoa::find($servidor->pes_id);
            $pessoa->update([
                'pes_nome' => $request->pes_nome,
                'pes_data_nascimento' => $request->pes_data_nascimento,
                'pes_sexo' => $request->pes_sexo,
                'pes_mae' => $request->pes_mae,
                'pes_pai' => $request->pes_pai,
            ]);

            // Atualizar servidor temporário
            $servidor->update([
                'st_data_admissao' => $request->st_data_admissao,
                'st_data_demissao' => $request->st_data_demissao,
            ]);

            // Atualizar endereços se fornecidos
            if ($request->has('enderecos')) {
                // Remover endereços antigos
                $pessoa->enderecos()->detach();
                
                foreach ($request->enderecos as $enderecoData) {
                    if (isset($enderecoData['end_id'])) {
                        // Atualizar endereço existente
                        $endereco = Endereco::find($enderecoData['end_id']);
                        $endereco->update([
                            'end_tipo_logradouro' => $enderecoData['end_tipo_logradouro'] ?? null,
                            'end_logradouro' => $enderecoData['end_logradouro'],
                            'end_numero' => $enderecoData['end_numero'] ?? null,
                            'end_bairro' => $enderecoData['end_bairro'] ?? null,
                            'cid_id' => $enderecoData['cid_id'],
                        ]);
                    } else {
                        // Criar novo endereço
                        $endereco = Endereco::create([
                            'end_tipo_logradouro' => $enderecoData['end_tipo_logradouro'] ?? null,
                            'end_logradouro' => $enderecoData['end_logradouro'],
                            'end_numero' => $enderecoData['end_numero'] ?? null,
                            'end_bairro' => $enderecoData['end_bairro'] ?? null,
                            'cid_id' => $enderecoData['cid_id'],
                        ]);
                    }
                    
                    $pessoa->enderecos()->attach($endereco->end_id);
                }
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Servidor temporário atualizado com sucesso',
                'data' => $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.enderecos.cidade'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao atualizar servidor temporário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $servidor = ServidorTemporario::find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor temporário não encontrado'], 404);
        }
        
        DB::beginTransaction();
        try {
            $pessoaId = $servidor->pes_id;
            
            // Excluir servidor temporário
            $servidor->delete();
            
            // Excluir pessoa associada
            Pessoa::find($pessoaId)->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Servidor temporário excluído com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao excluir servidor temporário',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
