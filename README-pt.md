[English](README.md)  [日本語](README-jp.md)[Español](README-es.md) 
[العربية](README-ar.md)  [Português](README-pt.md)
#### PHP Supply & Demand Admin Platform

**PHP Supply & Demand Admin Platform** é uma plataforma de gerenciamento de oferta e demanda leve construída usando PHP. Fornecer um sistema de administração backend para gerenciar informações de oferta e demanda de produtos, submissões de usuários e operações básicas de negócios. Projetada para pequenas e médias empresas, esta plataforma ajuda a simplificar a correspondência de recursos de oferta e demanda.

##### 🌟 Recursos

- 🔐 **Painel de Administração**
  Interface de backend simples e limpa para gerenciar todas as operações da plataforma.

- 📦 **Listagens de Oferta e Demanda**
  Adicionar, editar e excluir entradas de oferta/demanda com categorização e tags opcionais.

- 📝 **Submissões de Usuários**
  Suporte a conteúdo enviado por usuários (formulários de oferta ou demanda), pendentes de aprovação pelos administradores.

- 🔍 **Busca e Filtragem**
  Pesquisa básica por palavras-chave e filtro por categorias para melhorar a acessibilidade dos dados.

- 📊 **Visão Geral de Estatísticas**
  Visão geral do total de oferta, demanda, dados de usuários e tendências de publicação.

- 🧩 **Arquitetura Modular**
  Facilmente extensível com módulos adicionais ou integrações.

##### 🛠️ Pilha Tecnológica

- **Backend:** PHP (Framework ThinkPHP)
- **Frontend:** HTML + CSS + JavaScript (modelos de UI de administração)
- **Banco de Dados:** MySQL
- **Outros:** jQuery, Bootstrap (uso de UI legado)

##### 🚀 Início Rápido

###### Pré-requisitos

- PHP >= 7.1
- MySQL >= 5.6
- Apache / Nginx
- Composer (opcional, se você quiser gerenciar pacotes)

###### Instalação

1. Clone o projeto:

```bash
git clone https://github.com/feng-lai/php-supply-admin.git
cd php-supply-admin
```

2. Importe o esquema SQL para o seu banco de dados MySQL (por exemplo, `supply_admin.sql`).

3. Configure o banco de dados em `/application/database.php` ou `/config/database.php`.

```php
'hostname' => '127.0.0.1',
'database' => 'your_db_name',
'username' => 'your_db_user',
'password' => 'your_db_pass',
```

4. Implante o projeto em seu servidor web (Apache/Nginx) apontando para o diretório `/public` como raiz.

5. Acesse o painel de administração via:

```
http://yourdomain.com/admin
```

Credenciais padrão (se disponíveis na semente do banco de dados):
**Usuário:** admin
**Senha:** admin123 *(Por favor, altere após o primeiro login)*

##### 📁 Estrutura do Projeto

```
php-supply-admin/
├── application/     # Lógica de aplicação principal (controllers, models, views)
├── addons/          # Diretório raiz da web
├── extend/          # Arquivos de configuração
├── public/          # Recursos estáticos (CSS, JS, imagens)
└── vendor/          # (opcional) Arquivos de esquema de banco de dados ou sementes
```

##### 📌 Notas

* Este projeto é adequado para implantações internas ou de nível SME.
* Para maior segurança, habilite o SSL e adicione validação de entrada se usado em produção.
* O código legado pode precisar ser atualizado para versões mais novas do PHP ou frameworks.

##### 📄 Licença

Este projeto é de código aberto para aprendizado e customização. Consulte o repositório ou entre em contato com o autor para obter os termos de licença.

##### 🙋 Autor

Mantido por [feng-lai](https://github.com/feng-lai)
