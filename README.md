# FinanceFlow

Aplicativo web de finanças pessoais: acompanhe contas, cartões de crédito, gastos por categoria, orçamentos e metas de economia, tudo em reais (R$).

## Funcionalidades

- **Contas de usuário** – cadastro, login (com limite de tentativas) e logout. Cada usuário só enxerga e altera os próprios dados: contas, cartões, transações, orçamentos, metas e transferências são filtrados por usuário em todas as consultas, e há testes de isolamento entre usuários.
- **Visão geral** – saldo, entradas e saídas do período, gastos por categoria, orçamentos, metas e transações recentes.
- **Transações** – receitas e despesas por conta ou cartão, com filtros por mês, texto, tipo e categoria, e totais do mês.
- **Importar extrato** – na página de Transações, envie o extrato ou a fatura do banco em Excel (.xlsx/.xls), PDF (com texto selecionável) ou OFX, para uma conta ou cartão. O app mostra uma prévia, sugere a categoria pelo seu histórico, desmarca o que já foi lançado antes e só salva o que você confirmar. Planilhas precisam de uma linha de cabeçalho com Data e Valor (ou Crédito e Débito); em PDF, vale cada linha que começa com a data e traz um valor em R$.
- **Recorrentes** – lançamentos que se repetem todo mês (salário, aluguel, assinaturas), em conta ou cartão, com data de início e fim opcional e opção de pausar. O app cria os lançamentos no dia certo, sem duplicar, e dias 29 a 31 usam o último dia de meses curtos.
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

As fotos de perfil ficam em `storage/app/public`. Para o navegador enxergá-las, crie o link uma vez:

```bash
php artisan storage:link
```

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

Ao abrir o app você cai na tela de login. Crie sua conta em **Criar conta** (`/cadastro`) e comece do zero.

### Lançamentos recorrentes

Os lançamentos recorrentes são criados pelo comando `php artisan finance:recurring`, agendado para rodar todo dia. Em produção, deixe o cron do Laravel ativo:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Mesmo sem o agendador, abrir a Visão geral, as Transações ou as Recorrentes já coloca em dia o que venceu para o usuário logado. Para rodar à mão: `php artisan finance:recurring`.

### Dados de demonstração

Para preencher um usuário de demonstração com contas, cartões, transações dos últimos 6 meses, orçamentos e metas:

```bash
php artisan db:seed --class=DemoSeeder
```

Depois entre com `demo@financeflow.test` e a senha `demo1234`.

### Dados anteriores ao login

Registros criados antes de existir login ficam sem dono e não aparecem para ninguém. Para entregá-los a um usuário já cadastrado:

```bash
php artisan finance:claim seu@email.com
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
