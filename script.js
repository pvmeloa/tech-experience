// Melhoria progressiva: o formulário funciona sem JS; o script só melhora a UX.
// A validação real e definitiva acontece em processar.php.
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-inscricao');
  const mensagem = document.getElementById('mensagem');
  const contador = document.getElementById('contador');
  const grupoOficinas = document.getElementById('grupo-oficinas');
  const erroOficinas = document.getElementById('erro-oficinas');

  if (!form) return;

  // ---- 1. Contador de caracteres em tempo real ----
  const limite = Number(mensagem.getAttribute('maxlength')) || 200;

  function atualizarContador() {
    const restantes = limite - mensagem.value.length;
    contador.textContent = `Restam ${restantes} caractere${restantes === 1 ? '' : 's'}`;
    contador.classList.toggle('alerta', restantes <= 20);
  }
  mensagem.addEventListener('input', atualizarContador);
  atualizarContador();

  // ---- Utilitários de feedback ----
  function marcarCampo(campo, invalido, texto) {
    campo.classList.toggle('is-invalid', invalido);
    campo.setAttribute('aria-invalid', invalido ? 'true' : 'false');

    const id = `erro-${campo.id}`;
    let msg = document.getElementById(id);
    if (invalido) {
      if (!msg) {
        msg = document.createElement('p');
        msg.id = id;
        msg.className = 'error-message';
        msg.setAttribute('role', 'alert');
        campo.insertAdjacentElement('afterend', msg);
        campo.setAttribute('aria-describedby', id);
      }
      msg.textContent = texto;
    } else if (msg) {
      msg.remove();
      campo.removeAttribute('aria-describedby');
    }
  }

  function validarCampo(campo) {
    const v = campo.value.trim();
    switch (campo.id) {
      case 'nome':
        marcarCampo(campo, v.length < 3, 'Informe o nome completo (mínimo 3 caracteres).');
        return v.length >= 3;
      case 'email': {
        const ok = v !== '' && campo.validity.valid;
        marcarCampo(campo, !ok, 'Informe um e-mail válido.');
        return ok;
      }
      case 'experiencia':
        marcarCampo(campo, v === '', 'Selecione o nível de experiência.');
        return v !== '';
      default:
        return true;
    }
  }

  function validarOficinas() {
    const marcadas = form.querySelectorAll('input[name="oficinas[]"]:checked').length;
    const ok = marcadas > 0;
    grupoOficinas.classList.toggle('is-invalid', !ok);
    erroOficinas.hidden = ok;
    return ok;
  }

  function validarTurno() {
    const ok = form.querySelector('input[name="turno"]:checked') !== null;
    const grupo = form.querySelector('input[name="turno"]').closest('fieldset');
    grupo.classList.toggle('is-invalid', !ok);
    return ok;
  }

  // Validação interativa ao sair do campo
  ['nome', 'email', 'experiencia'].forEach((id) => {
    const campo = document.getElementById(id);
    campo.addEventListener('blur', () => validarCampo(campo));
    campo.addEventListener('input', () => {
      if (campo.classList.contains('is-invalid')) validarCampo(campo);
    });
  });
  form.querySelectorAll('input[name="oficinas[]"]').forEach((cb) =>
    cb.addEventListener('change', validarOficinas)
  );
  form.querySelectorAll('input[name="turno"]').forEach((r) =>
    r.addEventListener('change', validarTurno)
  );

  // ---- 2. Prevenção de envio inválido ----
  form.addEventListener('submit', (event) => {
    const campos = ['nome', 'email', 'experiencia'].map((id) => document.getElementById(id));
    const camposOk = campos.map(validarCampo).every(Boolean);
    const turnoOk = validarTurno();
    const oficinasOk = validarOficinas();

    if (!camposOk || !turnoOk || !oficinasOk) {
      event.preventDefault();
      const primeiroInvalido = form.querySelector('.is-invalid');
      if (primeiroInvalido) {
        const alvo = primeiroInvalido.matches('fieldset')
          ? primeiroInvalido.querySelector('input')
          : primeiroInvalido;
        alvo.focus();
      }
    }
  });
});
