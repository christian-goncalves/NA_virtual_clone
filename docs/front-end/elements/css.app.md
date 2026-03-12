1️⃣ :root { ... }
:root{
--background:0 0% 100%;
--foreground:216 28% 20%;
--card:0 0% 100%;
--card-foreground:216 28% 20%;
--popover:0 0% 100%;
--popover-foreground:216 28% 20%;
--primary:217 100% 32%;
--primary-foreground:0 0% 100%;
--secondary:220 14% 96%;
--secondary-foreground:217 100% 32%;
--muted:220 14% 96%;
--muted-foreground:215 8% 45%;
--accent:24 100% 60%;
--accent-foreground:0 0% 100%;
--destructive:0 84.2% 60.2%;
--destructive-foreground:0 0% 98%;
--border:220 13% 91%;
--input:220 13% 91%;
--ring:217 100% 32%;
--radius:.625rem;

--na-blue:217 100% 32%;
--na-light-blue:217 100% 42%;
--na-gold:48 100% 50%;

--priority-1:20 100% 60%;
--priority-2:33 100% 58%;
--priority-3:122 44% 57%;
--priority-4:200 13% 55%;

--whatsapp:142 70% 49%;
}
2️⃣ Variáveis --na-*
--na-blue:217 100% 32%;
--na-light-blue:217 100% 42%;
--na-gold:48 100% 50%;

Convertendo para HEX aproximado:

variável	HSL	HEX aproximado
na-blue	217 100% 32%	#0046A3
na-light-blue	217 100% 42%	#0060D1
na-gold	48 100% 50%	#FFC400
3️⃣ .bg-card
.bg-card{
background-color:hsl(var(--card))
}

--card:

--card:0 0% 100%

Resultado:

background-color:#FFFFFF

Ou seja:

cards são brancos.

4️⃣ .text-foreground
.text-foreground{
color:hsl(var(--foreground))
}

--foreground:

--foreground:216 28% 20%

HEX aproximado:

#2B3A4A

Cor padrão de texto.

5️⃣ .border-border
.border-border{
border-color:hsl(var(--border))
}

--border:

--border:220 13% 91%

HEX aproximado:

#E6E9EE

Borda clara de card.

6️⃣ .text-na-blue
.text-na-blue{
color:hsl(var(--na-blue))
}

Resultado:

#0046A3
7️⃣ Fonte usada (extraído também)

Fonte principal:

Inter

Aplicação:

font-family:var(--font-inter),system-ui,sans-serif

Fonte de destaque (display):

Space Grotesk
8️⃣ Raio dos cards (importante)

No :root:

--radius:.625rem

.rounded-lg:

border-radius:var(--radius)

Resultado:

0.625rem = 10px

Cards usam ~10px de radius.

9️⃣ Border lateral azul dos cards

Também aparece:

border-l-[hsl(var(--na-blue))]

Ou seja:

border-left: 4px solid #0046A3

Esse é o marcador azul dos cards.

🎯 Resultado — Design tokens principais do site
:root{
--background:#FFFFFF;
--foreground:#2B3A4A;
--card:#FFFFFF;
--border:#E6E9EE;

--na-blue:#0046A3;
--na-light-blue:#0060D1;
--na-gold:#FFC400;

--radius:10px;
}
