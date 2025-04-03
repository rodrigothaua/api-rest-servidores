<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cidade;

class CidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cidades = [
            ['cid_nome' => 'São Paulo', 'cid_uf' => 'SP'],
            ['cid_nome' => 'Rio de Janeiro', 'cid_uf' => 'RJ'],
            ['cid_nome' => 'Belo Horizonte', 'cid_uf' => 'MG'],
            ['cid_nome' => 'Salvador', 'cid_uf' => 'BA'],
            ['cid_nome' => 'Brasília', 'cid_uf' => 'DF'],
            ['cid_nome' => 'Curitiba', 'cid_uf' => 'PR'],
            ['cid_nome' => 'Fortaleza', 'cid_uf' => 'CE'],
            ['cid_nome' => 'Recife', 'cid_uf' => 'PE'],
            ['cid_nome' => 'Porto Alegre', 'cid_uf' => 'RS'],
            ['cid_nome' => 'Manaus', 'cid_uf' => 'AM'],
        ];

        foreach ($cidades as $cidade) {
            Cidade::create($cidade);
        }
    }
}
