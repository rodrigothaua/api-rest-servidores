<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unidade;
use App\Models\Endereco;

class UnidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar algumas unidades de exemplo
        $unidades = [
            [
                'unid_nome' => 'Secretaria de Administração',
                'unid_sigla' => 'SECAD',
                'endereco' => [
                    'end_tipo_logradouro' => 'Avenida',
                    'end_logradouro' => 'Paulista',
                    'end_numero' => 1000,
                    'end_bairro' => 'Bela Vista',
                    'cid_id' => 1 // São Paulo
                ]
            ],
            [
                'unid_nome' => 'Secretaria de Educação',
                'unid_sigla' => 'SECED',
                'endereco' => [
                    'end_tipo_logradouro' => 'Rua',
                    'end_logradouro' => 'da Educação',
                    'end_numero' => 500,
                    'end_bairro' => 'Centro',
                    'cid_id' => 2 // Rio de Janeiro
                ]
            ],
            [
                'unid_nome' => 'Secretaria de Saúde',
                'unid_sigla' => 'SECSA',
                'endereco' => [
                    'end_tipo_logradouro' => 'Avenida',
                    'end_logradouro' => 'da Saúde',
                    'end_numero' => 750,
                    'end_bairro' => 'Savassi',
                    'cid_id' => 3 // Belo Horizonte
                ]
            ],
        ];

        foreach ($unidades as $unidadeData) {
            $unidade = Unidade::create([
                'unid_nome' => $unidadeData['unid_nome'],
                'unid_sigla' => $unidadeData['unid_sigla'],
            ]);

            $endereco = Endereco::create([
                'end_tipo_logradouro' => $unidadeData['endereco']['end_tipo_logradouro'],
                'end_logradouro' => $unidadeData['endereco']['end_logradouro'],
                'end_numero' => $unidadeData['endereco']['end_numero'],
                'end_bairro' => $unidadeData['endereco']['end_bairro'],
                'cid_id' => $unidadeData['endereco']['cid_id'],
            ]);

            $unidade->enderecos()->attach($endereco->end_id);
        }
    }
}
