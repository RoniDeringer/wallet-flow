# Recap Geral — Live Coding / Backend / Frontend / Arquitetura

---

# Mentalidade Durante o Live Coding

O avaliador normalmente observa:

- raciocínio
- clareza
- organização
- arquitetura
- separação de responsabilidades
- debugging
- comunicação técnica

Inclusive falar enquanto pensa ajuda muito.

Frases úteis:

```text
"Vou mover isso para um service porque isso é regra de negócio."
```

```text
"Essa validação pertence ao request."
```

```text
"Isso poderia virar um repository caso a persistência cresça."
```

```text
"Vou usar async/await para melhorar legibilidade."
```

---

# Hook

Hook = ponto de extensão do fluxo.

Você “engancha” lógica em algum momento do sistema.

**Vuejs** = mounted, watch, onUnmounted...

**Laravel** = Events, Listeners, Middlewares...

---

# Async / Await

## Conceito

```text
await pausa a execução até a Promise resolver
```

---

# Ref / Reactive

```text
ref é para valores únicos/primitivos (string, number, boolean, arrays simples) e acessa com .value.
```

```text
reactive é para objetos/estruturas complexas e acessa direto (form.name)
```

---

# Observer / Listener

```text
Observer escuta eventos de um Model específico automaticamente (created, updated, etc).
```

```text
Listener escuta Events da aplicação inteira, normalmente desacoplados do Model e mais genéricos/escaláveis.
```

---

# Promise.all

```text
executa várias operações assíncronas ao mesmo tempo.
```

espera TODAS terminarem
retorna tudo junto

---

### Benefícios

- paralelismo
- mais performance
- menor tempo total

---

# Debounce (Idempotência)

### O que é

Evita executar uma função várias vezes seguidas.

Muito usado em:

- search
- filtros
- autocomplete

---

### Exemplo

```js
let timeout;

function debounce(callback, delay = 300) {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        callback();
    }, delay);
}
```

---

### Problema que resolve

Sem debounce:

- request a cada tecla digitada

Com debounce:

- request apenas após o usuário parar de digitar

---

# Lazy Loading

## O que é

Carregar algo apenas quando necessário.

---

## Usado em

- rotas
- componentes
- imagens
- tabelas grandes

---

## Vue

```js
const OrdersPage = () => import("./OrdersPage.vue");
```

---

## Benefícios

- carregamento inicial mais rápido
- menor bundle
- melhor performance

---

# Memoization

### O que é

Guardar resultado de operações pesadas em cache.

---

### Exemplo

```js
const cache = {};

function calculate(id) {
    if (cache[id]) {
        return cache[id];
    }

    const result = expensiveOperation();

    cache[id] = result;

    return result;
}
```

---

### Problema que resolve

Evita recalcular:

- queries pesadas
- filtros complexos
- cálculos repetidos

---
