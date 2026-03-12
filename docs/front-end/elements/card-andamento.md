```markdown
# Front-end Refactor Guide — NA Virtual Clone (Laravel + Tailwind)

Este documento define **tokens visuais, configuração do Tailwind e componentes Blade** necessários para alinhar o front-end do projeto **NA_virtual_clone** ao layout do site de referência.

Objetivo:

- padronizar cores e tokens
- registrar design tokens no Tailwind
- criar componente Blade reutilizável de **card de reunião**
- estruturar grid responsivo
- garantir fidelidade visual ao site original

⚠️ Regras:

- **não alterar backend**
- **não alterar controllers**
- **não alterar contratos de dados**
- somente **views + Tailwind**

---

# 1. Design Tokens

Adicionar tokens no arquivo:

```

resources/css/app.css

````

Inserir:

```css
:root {

  --background: 0 0% 100%;
  --foreground: 216 28% 20%;

  --card: 0 0% 100%;
  --border: 220 13% 91%;

  --na-blue: 217 100% 32%;
  --na-light-blue: 217 100% 42%;
  --na-gold: 48 100% 50%;

  --radius: 0.625rem;

}
````

---

# 2. Tailwind Config

Editar:

```
tailwind.config.js
```

Adicionar tokens ao `extend`.

```javascript
theme: {
  extend: {

    colors: {

      na: {
        blue: "hsl(var(--na-blue))",
        light: "hsl(var(--na-light-blue))",
        gold: "hsl(var(--na-gold))",
      },

      background: "hsl(var(--background))",
      foreground: "hsl(var(--foreground))",

      card: "hsl(var(--card))",
      border: "hsl(var(--border))"

    },

    borderRadius: {

      card: "0.625rem"

    }

  }
}
```

Depois disso será possível usar:

```
bg-na-blue
text-na-blue
bg-card
border-border
rounded-card
```

---

# 3. Tipografia

O site usa duas fontes:

| uso     | fonte         |
| ------- | ------------- |
| texto   | Inter         |
| títulos | Space Grotesk |

Adicionar no layout principal:

```
resources/views/layouts/app.blade.php
```

Dentro do `<head>`:

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
```

Criar utilitário no CSS:

```css
.font-display {
  font-family: 'Space Grotesk', sans-serif;
}
```

---

# 4. Componente Blade — Meeting Card

Criar arquivo:

```
resources/views/components/meeting/card.blade.php
```

Conteúdo:

```blade
<div class="bg-card rounded-xl p-4 flex flex-col gap-2
            transition-all hover:shadow-lg
            border-l-4 border-l-na-blue
            border border-blue-100">

    {{-- status + tempo --}}
    <div class="flex items-center justify-between gap-2">

        <span class="inline-block px-2 py-0.5 rounded-full
                     text-[11px] font-bold
                     bg-na-blue text-white">
            {{ $status }}
        </span>

        <span class="text-[11px] text-amber-500 font-medium">
            {{ $remaining }}
        </span>

    </div>

    {{-- nome do grupo --}}
    <h3 class="font-bold text-sm text-foreground leading-snug line-clamp-2">
        {{ $meeting->name }}
    </h3>

    {{-- tags --}}
    <div class="flex items-center gap-1.5 flex-wrap">

        @if($meeting->is_closed)

            <span class="inline-flex items-center gap-1
                         px-2 py-0.5
                         rounded-full
                         text-[10px] font-bold
                         bg-slate-100 text-slate-600
                         border border-slate-200">

                Fechada

            </span>

        @endif

    </div>

    {{-- horário --}}
    <div class="text-sm font-bold text-na-blue font-display">

        {{ $meeting->start }} – {{ $meeting->end }}

    </div>

    {{-- dados zoom --}}
    <div class="text-xs text-gray-500 leading-relaxed">

        Zoom · ID: {{ $meeting->zoom_id }} · Senha: {{ $meeting->password }}

    </div>

    {{-- ações --}}
    <div class="flex items-center gap-2 mt-auto pt-1">

        <a href="{{ $meeting->link }}"
           target="_blank"
           rel="noopener noreferrer"

           class="flex-1 flex items-center justify-center gap-1.5
                  py-2.5 rounded-lg
                  text-xs font-bold
                  transition-all hover:-translate-y-0.5
                  bg-na-blue hover:bg-na-light
                  text-white">

            Entrar

        </a>

        <button
            class="flex items-center justify-center
                   w-8 h-8
                   rounded-full
                   bg-gray-100 text-gray-500
                   hover:bg-gray-200
                   transition-colors">

            ⤴

        </button>

    </div>

</div>
```

---

# 5. Grid das reuniões

Na view principal:

```
resources/views/reunioes-virtuais.blade.php
```

Implementar:

```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

    @foreach($meetings as $meeting)

        <x-meeting.card
            :meeting="$meeting"
            :status="$meeting->status"
            :remaining="$meeting->remaining" />

    @endforeach

</div>
```

Grid responsivo:

| viewport | colunas |
| -------- | ------- |
| mobile   | 1       |
| tablet   | 2       |
| desktop  | 3       |

---

# 6. Estrutura recomendada de componentes

```
resources/views/components/

    meeting/
        card.blade.php

    ui/
        badge.blade.php
        button-primary.blade.php
        button-circle.blade.php
```

---

# 7. Resultado visual esperado

Card deve possuir:

✔ fundo branco
✔ borda azul lateral
✔ radius 10px
✔ sombra hover
✔ botão azul institucional
✔ badges arredondados
✔ grid responsivo

---

# 8. Próxima etapa de refactor

Após implementar o card, o próximo componente a alinhar é:

```
Hero section
```

Elementos do hero:

* gradiente azul
* título principal
* botão "Reuniões Online"
* botão "Ligar Agora"
* botão "Sala perto de você"

Este bloco define o **layout visual da página**.

---

# 9. Tarefa para Codex

Executar automaticamente:

1. atualizar `resources/css/app.css`
2. atualizar `tailwind.config.js`
3. criar `components/meeting/card.blade.php`
4. aplicar grid na view de reuniões

Sem alterar backend.

---

```
```
