Estruta base do Programa
logi/
├── index.php # Dashboard (Home)
├── produtos.php # Listagem, cadastro e edição de produtos
├── enderecos.php # Listagem, cadastro e edição de endereços
├── transportadores.php # Listagem, cadastro e edição de transportadores
├── fretes.php # Listagem e criação de fretes
├── relatorios.php # Filtro por data e download do relatório
├── includes/
│ ├── header.php # <head> com Tailwind CSS + barra superior
│ ├── sidebar.php # Menu lateral (Home, Produtos, Endereços, Transportadores, Fretes, Relatórios)
│ └── footer.php # Fechamento do HTML e scripts JS
├── config/
│ └── database.php # Conexão PDO SQLite + criação automática das tabelas
└── data/ # Onde ficará o logistica.sqlite
