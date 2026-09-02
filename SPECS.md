# Project Goal
- Read a job position description from LinkedIn
and build a resume that would increase my changes
to get an interview for that job position.

## Project main workflows
- Expose a POST end-point that will receive a payload like this:

```
{
    "job_description": "Sobre a vaga A Gestor Seller é uma startup de tecnologia, no setor de software como serviço(SaaS), dedicada a ajudar vendedores de marketplaces. Estamos comprometidos em agregar valor e auxiliar na operação dos nossos clientes. Nesse cargo, você será referência técnica do time: conduzirá as funcionalidades mais críticas do roadmap, participará de decisões de arquitetura e será responsável por elevar a qualidade técnica e a produtividade dos demais desenvolvedores. Responsabilidades Liderar tecnicamente o desenvolvimento de funcionalidades complexas do roadmap Participar de decisões de arquitetura e desenho de soluções Resolver problemas críticos de performance, escalabilidade e estabilidade Revisar PR 's garantindo padrões de qualidade e segurançaMentorar desenvolvedores juniores e plenosDocumentar arquitetura, padrões e decisões técnicasAntecipar riscos técnicos e propor melhorias estruturais no sistemaQualificaçõesExperiência avançada com Laravel e Vue em sistemas de produção com escalaDomínio de design patterns, princípios SOLID e clean code, com capacidade de definir e disseminar padrões no timeConhecimento em DDD (Domain-Driven Design) e experiência aplicando modelagem de domínio em sistemas reaisExperiência com arquitetura de software: camadas, separação de responsabilidades e desenho de módulos/serviçosDomínio de Git, code review e boas práticas de versionamentoExperiência sólida com Linux e ambientes de produçãoDomínio de MySQL: modelagem, otimização de queries e diagnóstico de gargalosExperiência com filas, jobs assíncronos e processamento em backgroundExperiência com integrações de APIs de terceiros em larga escalaCapacidade de tomar decisões técnicas considerando trade-offs de negócioHabilidade de comunicação e mentoriaDiferenciaisExperiência com infraestrutura AWS (EC2, RDS, S3, SQS)Experiência prévia em SaaS B2B ou integrações com marketplacesVivência com observabilidade e monitoramento de aplicaçõesModelo de trabalho CLT, presencial, 40h semanais, em Taubaté-SP.Além do salário, temos alguns benefícios:Plano de Saúde (Santa Casa)Vale Alimentação/Refeição (R$30/dia trabalhado)Auxílio Transporte (R$150/mês) no cartão multibenefícios.TotalPassDay Off no aniversárioNosso escritório fica localizado no bairro Jardim das Nações."
}
```
- Read a RESUME.md file that will contain all my job experience information + skills.
- Build a resume based on that RESUME.md file.
- Generate a PDF file for that resume.
- Send that PDF back on the response of that POST end-point.

## Restrictions

1. **LLM Provider** — Google Gemini API (free tier, no credit card required)
2. **RESUME.md Format** — Follows a defined schema (e.g., YAML frontmatter + markdown)
3. **PDF Library** — dompdf
4. **Language Handling** — The generated resume should match the language of the job description input
5. **Error Handling**
    - A 400 response for invalid inputs (missing `job_description`, or `job_description` too short).
    - A 502 response for LLM timeouts
    - A 500 response for any other issues (like PDF generation failure)
6. **Rate Limiting / Timeouts** — On-disk cache keyed on hash of (job_description + RESUME.md) to avoid regenerating identical resumes; use `symfony/rate-limiter` set to Gemini's free-tier quota (10 RPM) to stay under model limits.

