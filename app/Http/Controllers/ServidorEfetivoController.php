<?php

namespace App\Http\Controllers;

use App\Models\Pessoa;
use App\Models\ServidorEfetivo;
use App\Models\Endereco;
use App\Models\Cidade;
use App\Models\FotoPessoa;
use App\Models\Lotacao;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServidorEfetivoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        
        $query = ServidorEfetivo::with(['pessoa', 'pessoa.fotos']);
        
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
            'se_matricula' => 'required|string|max:20',
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

            // Criar servidor efetivo
            $servidor = ServidorEfetivo::create([
                'pes_id' => $pessoa->pes_id,
                'se_matricula' => $request->se_matricula,
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
                'message' => 'Servidor efetivo criado com sucesso',
                'data' => $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.enderecos.cidade'])
            ], 201);
        } catch (\Exception $e) {
            DB::commit();
            
            return response()->json([
                'message' => 'Servidor efetivo criado com sucesso',
                'data' => $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.enderecos.cidade'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao criar servidor efetivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $servidor = ServidorEfetivo::with([
            'pessoa', 
            'pessoa.enderecos', 
            'pessoa.enderecos.cidade', 
            'pessoa.fotos',
            'lotacoes',
            'lotacoes.unidade'
        ])->find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor efetivo não encontrado'], 404);
        }
        
        return response()->json($servidor);
    }

    public function update(Request $request, $id)
    {
        $servidor = ServidorEfetivo::find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor efetivo não encontrado'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'pes_nome' => 'required|string|max:200',
            'pes_data_nascimento' => 'nullable|date',
            'pes_sexo' => 'nullable|string|max:1',
            'pes_mae' => 'nullable|string|max:200',
            'pes_pai' => 'nullable|string|max:200',
            'se_matricula' => 'required|string|max:20',
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

            // Atualizar servidor efetivo
            $servidor->update([
                'se_matricula' => $request->se_matricula,
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
                'message' => 'Servidor efetivo atualizado com sucesso',
                'data' => $servidor->load(['pessoa', 'pessoa.enderecos', 'pessoa.enderecos.cidade'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao atualizar servidor efetivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $servidor = ServidorEfetivo::find($id);
        
        if (!$servidor) {
            return response()->json(['message' => 'Servidor efetivo não encontrado'], 404);
        }
        
        DB::beginTransaction();
        try {
            $pessoaId = $servidor->pes_id;
            
            // Excluir servidor efetivo
            $servidor->delete();
            
            // Excluir pessoa associada
            Pessoa::find($pessoaId)->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Servidor efetivo excluído com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao excluir servidor efetivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function servidoresPorUnidade($unidId)
    {
        $perPage = request()->input('per_page', 10);
        $page = request()->input('page', 1);
        
        $servidores = ServidorEfetivo::with(['pessoa', 'pessoa.fotos'])
            ->whereHas('lotacoes', function($query) use ($unidId) {
                $query->where('unid_id', $unidId)
                    ->where(function($q) {
                        $q->whereNull('lot_data_remocao')
                        ->orWhere('lot_data_remocao', '>=', now());
                    });
            })
            ->paginate($perPage, ['*'], 'page', $page);
        
        $result = $servidores->map(function($servidor) {
            $pessoa = $servidor->pessoa;
            $lotacao = $servidor->lotacoes()
                ->where('unid_id', request()->route('unid_id'))
                ->whereNull('lot_data_remocao')
                ->orWhere('lot_data_remocao', '>=', now())
                ->with('unidade')
                ->first();
            
            $foto = $pessoa->fotos->first();
            $fotoUrl = null;
            
            if ($foto) {
                // Gerar URL temporária para a foto
                $fotoUrl = app('App\Http\Controllers\FotoController')->getTemporaryUrl($foto->fp_hash);
            }
            
            return [
                'nome' => $pessoa->pes_nome,
                'idade' => $pessoa->getIdade(),
                'unidade_lotacao' => $lotacao ? $lotacao->unidade->unid_nome : null,
                'foto_url' => $fotoUrl
            ];
        });
        
        return response()->json([
            'data' => $result,
            'meta' => [
                'current_page' => $servidores->currentPage(),
                'from' => $servidores->firstItem(),
                'last_page' => $servidores->lastPage(),
                'per_page' => $servidores->perPage(),
                'to' => $servidores->lastItem(),
                'total' => $servidores->total(),
            ]
        ]);
    }

    public function enderecoFuncional($nome)
    {
        $perPage = request()->input('per_page', 10);
        $page = request()->input('page', 1);
        
        $servidores = ServidorEfetivo::with(['pessoa', 'lotacoes.unidade.enderecos.cidade'])
            ->whereHas('pessoa', function($query) use ($nome) {
                $query->where('pes_nome', 'ILIKE', "%{$nome}%");
            })
            ->paginate($perPage, ['*'], 'page', $page);
        
        $result = $servidores->map(function($servidor) {
            $pessoa = $servidor->pessoa;
            $lotacao = $servidor->lotacoes()
                ->whereNull('lot_data_remocao')
                ->orWhere('lot_data_remocao', '>=', now())
                ->with('unidade.enderecos.cidade')
                ->first();
            
            $enderecoFuncional = null;
            if ($lotacao && $lotacao->unidade && $lotacao->unidade->enderecos->isNotEmpty()) {
                $endereco = $lotacao->unidade->enderecos->first();
                $enderecoFuncional = [
                    'logradouro' => ($endereco->end_tipo_logradouro ? $endereco->end_tipo_logradouro . ' ' : '') . $endereco->end_logradouro,
                    'numero' => $endereco->end_numero,
                    'bairro' => $endereco->end_bairro,
                    'cidade' => $endereco->cidade->cid_nome,
                    'uf' => $endereco->cidade->cid_uf
                ];
            }
            
            return [
                'servidor' => [
                    'nome' => $pessoa->pes_nome,
                    'matricula' => $servidor->se_matricula
                ],
                'unidade' => $lotacao ? [
                    'nome' => $lotacao->unidade->unid_nome,
                    'sigla' => $lotacao->unidade->unid_sigla
                ] : null,
                'endereco_funcional' => $enderecoFuncional
            ];
        });
        
        return response()->json([
            'data' => $result,
            'meta' => [
                'current_page' => $servidores->currentPage(),
                'from' => $servidores->firstItem(),
                'last_page' => $servidores->lastPage(),
                'per_page' => $servidores->perPage(),
                'to' => $servidores->lastItem(),
                'total' => $servidores->total(),
            ]
        ]);
    }
}
