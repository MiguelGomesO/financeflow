# FinanceFlow

Aplicativo web de finanças pessoais: acompanhe contas, cartões de crédito, gastos por categoria, orçamentos e metas de economia, tudo em reais (R$).

## Funcionalidades

- **Visão geral** – saldo, entradas e saídas do período, gastos por categoria, orçamentos, metas e transações recentes.
- **Transações** – receitas e despesas por conta ou cartão, com filtros por mês, texto, tipo e categoria, e totais do mês.
- **Orçamentos** – limite mensal por categoria, com alerta ao se aproximar do teto.
- **Metas** – objetivos de economia com prazo, depósitos e quanto guardar por mês.
- **Contas e cartões** – saldo das contas, transferências entre contas e cartões de crédito com:
  - fatura em aberto calculada pelo ciclo de fechamento (ou informada à mão pelo botão **+**);
  - limite usado informado à mão, quando o banco mostra um valor diferente da soma das compras;
  - selos dos principais bancos e fintechs (veja [Logos dos bancos](#logos-dos-bancos)).

## Tecnologias

- PHP 8.2+ e [Laravel 12](https://laravel.com)
- [Livewire 4](https://livewire.laravel.com) e Alpine.js
- [Tailwind CSS 4](https://tailwindcss.com) com [Vite](https://vitejs.dev)
- SQLite, MySQL ou qualquer banco suportado pelo Laravel

## Requisitos

- PHP 8.2 ou superior (com as extensões usuais do Laravel)
- [Composer](https://getcomposer.org)
- Node.js e npm

## Instalação

```bash
git clone <url-do-repositorio> financeflow
cd financeflow
composer setup
```

O `composer setup` instala as dependências, cria o `.env` a partir do `.env.example`, gera a chave da aplicação, roda as migrations e compila os assets.

Por padrão o `.env.example` usa SQLite. Para usar MySQL, ajuste no `.env` antes das migrations:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=financeflow
DB_USERNAME=root
DB_PASSWORD=
```

Em seguida rode `php artisan migrate`.

## Executando

```bash
composer dev
```

Sobe o servidor (`http://localhost:8000`), a fila, os logs e o Vite. Se preferir, rode só o essencial em dois terminais:

```bash
php artisan serve
npm run dev
```

### Dados de demonstração

Para preencher o app com contas, cartões, transações dos últimos 6 meses, orçamentos e metas:

```bash
php artisan db:seed --class=DemoSeeder
```

## Testes

```bash
php artisan test
```

## Logos dos bancos

Os selos usam a cor e a sigla de cada banco (lista em `app/Support/Banks.php`). Para usar o logo oficial, coloque o arquivo em `public/images/banks/` com o nome do banco, por exemplo `nubank.svg`, `itau.png` ou `banco-do-brasil.svg`; ele substitui o selo automaticamente. Os logos são marcas dos respectivos bancos e não vêm no repositório.

## Estrutura

| Pasta | Conteúdo |
| --- | --- |
| `app/Livewire` | Páginas e formulários (Dashboard, Transações, Orçamentos, Metas, Contas) |
| `app/Models` | Modelos Eloquent (contas, cartões, transações, orçamentos, metas) |
| `app/Support` | Regras e utilitários (dinheiro, datas, bancos, status de orçamento) |
| `resources/views` | Views Blade e componentes |
| `resources/js` | Alpine, incluindo o calendário próprio (`date-picker.js`) |
| `database/seeders` | Dados de demonstração |
| `tests` | Testes de feature e unitários |
