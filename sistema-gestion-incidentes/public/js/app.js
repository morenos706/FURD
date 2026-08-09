document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth < 992 && sidebar.classList.contains('open')
          && !sidebar.contains(e.target) && e.target !== toggle) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Confirmacion antes de eliminar
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('submit', function (e) {
      const msg = el.getAttribute('data-confirm') || '¿Esta seguro de realizar esta accion?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach((el) => {
    setTimeout(() => { try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch (e) {} }, 6000);
  });

  // DataTables genericas
  document.querySelectorAll('table.data-table').forEach((table) => {
    if (window.jQuery) {
      window.jQuery(table).DataTable({
        language: {
          search: 'Buscar:',
          lengthMenu: 'Mostrar _MENU_ registros',
          info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
          paginate: { previous: 'Anterior', next: 'Siguiente' },
          zeroRecords: 'Sin resultados',
          infoEmpty: 'No hay registros disponibles',
        },
        pageLength: 25,
        order: [],
      });
    }
  });
});

/** Agrega una fila dinamica a un grupo repetible del formulario (personas, animales, edificaciones). */
function addRepeatRow(containerId, template) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const index = container.children.length;
  const html = template.replaceAll('__INDEX__', index);
  const wrapper = document.createElement('div');
  wrapper.innerHTML = html;
  container.appendChild(wrapper.firstElementChild);
}

function removeRepeatRow(btn) {
  const row = btn.closest('.repeat-row');
  if (row) row.remove();
}
