Objetivo
Construir uma aplicação Fullstack (Backend + Frontend) que permita aos usuários postar e visualizar comentários em um mural.
Tecnologias
Backend: Escolha uma (Python, PHP ou Node.js).
Frontend: Escolha uma (React ou Vue.js).
Requisitos do Backend (API REST)
Crie dois endpoints:
POST /comments
Ação: Recebe um JSON com {"name": "...", "message": "..."}.
Armazenamento: Salve o comentário. Não use um banco de dados complexo. Um array em memória no servidor ou um arquivo JSON é suficiente.
Retorno: O comentário que foi salvo.

GET /comments
Ação: Busca todos os comentários salvos.
Retorno: Um array JSON com todos os comentários (ex: [{"name": "...", "message": "..."}, ...]).
Requisitos do Frontend
Crie uma página única que contenha:
Formulário:
Um campo para "Nome".
Um campo para "Mensagem".
Um botão "Enviar".
Ao enviar, deve fazer a requisição POST para o seu backend.

Lista de Comentários:
Abaixo do formulário, exibe todos os comentários (nome e mensagem).
A lista deve ser carregada via GET da API assim que a página abrir.
(Bônus opcional): Atualizar a lista automaticamente após um novo envio.

Ao final do tempo, comprima todo o projeto (pastas backend e frontend) em repositório público no github e nos envie.
Considerações adicionais:
Código: Preze por uma boa apresentação e qualidade do código, assim como utilização de boas práticas.
Lógica: Valide se seu código foi feito seguindo uma lógica de programação e resolução do problema apresentado.
Interface: Ao criar sua tela foque no bom design da interface, usabilidade e experiência do usuário.
Prazo: Será avaliado a capacidade de entregar o trabalho dentro do tempo estipulado.
