<?php
// Cliente mínimo da Calculadora de Tributos (CBS/IBS/IS) da Receita Federal.
// PHP 8+ com a extensão curl (padrão no cPanel). Uso: php cliente.php

const CALCULADORA_PADRAO = 'https://piloto-cbs.tributos.gov.br/servico/calculadora-consumo/api/calculadora';

function calculadoraBase(): string
{
    return getenv('CALCULADORA_URL') ?: CALCULADORA_PADRAO;
}

function calcularTributos(array $operacao, int $timeout = 15): array
{
    $ch = curl_init(calculadoraBase() . '/observabilidade/regime-geral');
    $aviso = null;
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($operacao, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HEADERFUNCTION => function ($ch, $linha) use (&$aviso) {
            if (stripos($linha, 'x-warning-dados-simulados:') === 0) {
                $aviso = trim(substr($linha, strlen('x-warning-dados-simulados:')));
            }
            return strlen($linha);
        },
    ]);
    $corpo = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);

    if ($corpo === false || $status !== 200) {
        throw new RuntimeException("Calculadora falhou ($status): " . ($erro ?: $corpo));
    }

    $dados = json_decode($corpo, true, 512, JSON_THROW_ON_ERROR);
    $porEstado = fn(array $estados) => array_values(array_filter(
        $dados['objetos'],
        fn($o) => in_array($o['estadoItem'], $estados, true)
    ));

    return [
        'calculados' => $porEstado(['CALCULADO', 'CALCULO_SIMULADO']),
        'com_erro' => $porEstado(['INCONSISTENCIA_ENTRADA']),
        'para_revisar' => $porEstado(['NAO_IMPLEMENTADO', 'FALHA_TECNICA']),
        'total' => $dados['total'] ?? null,
        'aviso' => $aviso,
    ];
}

if (PHP_SAPI === 'cli' && realpath($argv[0]) === __FILE__) {
    $operacao = json_decode(file_get_contents(__DIR__ . '/operacao-2026.json'), true);
    $r = calcularTributos($operacao);
    foreach ($r['calculados'] as $item) {
        $g = $item['tribCalc']['IBSCBS']['gIBSCBS'];
        echo "Item {$item['nObj']}: {$item['estadoItem']} | CBS {$g['gCBS']['vCBS']} | IBS {$g['vIBS']}\n";
    }
    foreach ($r['com_erro'] as $item) {
        echo "Item {$item['nObj']}: {$item['erros'][0]['code']} {$item['erros'][0]['detail']}\n";
    }
    echo 'Totais: ' . json_encode($r['total']['tribCalc']['IBSCBSTot'], JSON_UNESCAPED_UNICODE) . "\n";
}
