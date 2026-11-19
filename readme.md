# Logi — Gerenciador Logístico em PHP

Aplicação web desenvolvida como **Atividade Prática Orientada (APO)** para a disciplina de **Fundamentos de Programação para Internet** (UNIPAR EAD), ministrada pelo professor **Huilson José Lorenzi**.

O sistema permite gerenciar o catálogo de produtos, pontos de origem e destino, transportadores, geração de fretes (com relacionamento **1:N** entre transportador e múltiplos produtos) e exportação de relatórios diários.

---

## Funcionalidades

- **Dashboard (`index.php`)**: Visão geral da operação com indicadores de total de produtos, transportadores ativos, fretes gerados no dia, listagem dos últimos fretes e atalhos rápidos[cite: 2].
- **Produtos (`produtos.php`)**: Cadastro, listagem com busca instantânea, edição e exclusão de produtos (ID, nome, preço e peso)[cite: 1, 3, 4].
- **Endereços (`enderecos.php`)**: Cadastro, listagem, edição e exclusão de endereços utilizados como origem e destino nos fretes[cite: 1, 5, 6].
- **Transportadores (`transportadores.php`)**: Cadastro, listagem, edição e exclusão de motoristas ou empresas transportadoras[cite: 1, 7].
- **Fretes (`fretes.php`)**: Geração de fretes vinculando **1 transportador**, **1 endereço de origem**, **1 endereço de destino** e **múltiplos produtos** com quantidades personalizadas e cálculo dinâmico de peso total em JavaScript[cite: 1, 8, 9].
- **Relatórios Diários (`relatorios.php`)**: Filtro por data específica e download do relatório de fretes do dia em formato CSV compatível com Excel[cite: 1, 10].

---

## Tecnologias Utilizadas

- **Backend**: PHP 8+ com PDO (`SQLite` embutido para persistência local sem necessidade de configuração externa)
- **Frontend**: HTML5, Tailwind CSS e JavaScript (manipulação dinâmica de seleção de produtos, cálculo de peso e filtros de busca)[cite: 1]
- **Ambiente Local**: XAMPP (Apache)[cite: 1]
- **Prototipação**: Figma[cite: 1]
- **Versionamento**: Git e GitHub[cite: 1]

---

## Estrutura de Diretórios

```text
Logi/
├── index.php              # Dashboard (Home)
├── produtos.php           # CRUD e busca de produtos
├── enderecos.php          # CRUD e busca de endereços (origem e destino)
├── transportadores.php    # CRUD e busca de transportadores
├── fretes.php             # Listagem, detalhes e geração de fretes (1:N)
├── relatorios.php         # Filtro por data e exportação do relatório diário
├── config/
│   └── database.php       # Conexão PDO SQLite e criação automática das tabelas
├── includes/
│   ├── header.php         # Cabeçalho HTML e barra superior
│   ├── sidebar.php        # Menu de navegação lateral
│   └── footer.php         # Fechamento do layout
├── data/
│   └── logistica.sqlite   # Banco de dados SQLite criado automaticamente
└── README.md              # Documentação do projeto

Como Executar Localmente (XAMPP)

    Clone este repositório ou copie a pasta Logi para dentro do diretório htdocs do seu XAMPP[cite: 1]:
    Bash

    git clone https://github.com/carlosUniAccount/Logi.git

    Inicie o serviço Apache no painel de controle do XAMPP[cite: 1].

    Certifique-se de que a extensão pdo_sqlite está habilitada no php.ini (padrão nas instalações recentes do XAMPP).

    Acesse no navegador:
    Plaintext

    http://localhost/Logi/index.php

    O arquivo do banco de dados (data/logistica.sqlite) e todas as tabelas são criados automaticamente no primeiro acesso.

Vídeo de Demonstração

    Link do vídeo (YouTube - Não Listado):

Autor

    Nome: Carlos Eduardo Campinas de Andrade

    RA: 09063110

    Instituição: UNIPAR EAD — Polo Altônia

    OBS: CarlosECampinas e minha conta padrao no vscode
```
