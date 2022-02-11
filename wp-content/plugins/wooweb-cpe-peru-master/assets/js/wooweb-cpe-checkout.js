jQuery(document).ready(function ($) {

  $(".ubigeo").css("display", "none");
  
  $(document).on('change', '#wooweb_cpe_tipo_documento', function () {

    $('#wooweb_cpe_razonsocial').val('');
    $('#wooweb_cpe_domiciliofiscal').val('');
    $('#wooweb_cpe_ubigeo').val('');

    if ($(this).val() == 6 || $(this).val() == 'RUC' || $(this).val() == '4' || $(this).val() == 'Carnet de Extranjería' || $(this).val() == '7' || $(this).val() == 'Pasaporte') {
      $(".wooweb-company").css("display", "block");
    } else {
      $(".wooweb-company").css("display", "none");
    }

    tipoDocumento = $("select#wooweb_cpe_tipo_documento option:selected").val();
    nroDocumento = $('#wooweb_cpe_registro').val();

  });

  $("#wooweb_cpe_registro").on("input", function () {
    tipoDocumento = $("select#wooweb_cpe_tipo_documento option:selected").val();
    nroDocumento = $('#wooweb_cpe_registro').val();

    if (tipoDocumento == 'DNI') {
      tipoDocumento = 1;
    } else if (tipoDocumento == 'RUC') {
      tipoDocumento = 6;
    } else if (tipoDocumento == 'Carnet de Extranjería') {
      tipoDocumento = 4;
    } else if (tipoDocumento == 'Pasaporte') {
      tipoDocumento = 7;
    }

    nombreDocumento = '';
    if (tipoDocumento == 1) {
      nombreDocumento = 'DNI';
    } else if (tipoDocumento == 6) {
      nombreDocumento = 'RUC';
    } else if (tipoDocumento == 4) {
      nombreDocumento = 'Carnet de Extranjería';
    } else if (tipoDocumento == 7) {
      nombreDocumento = 'Pasaporte';
    }
    //

    if ((tipoDocumento == 1 && nroDocumento.length == 8) || (tipoDocumento == 6 && nroDocumento.length == 11)) {
      $('#wooweb_cpe_razonsocial').val('');
      $('#wooweb_cpe_domiciliofiscal').val('');
      $('#wooweb_cpe_ubigeo').val('');
      //e.preventDefault();
      jQuery.ajax({
        type: "post",
        url: ajax_cpe_peru.ajax_url,
        beforeSend: function (qXHR, settings) {
          $('label[for="wooweb_cpe_razonsocial"]').html('Nombre <abbr class="required" title="obligatorio">* OBTENIENDO DATOS...</abbr>');
        },
        data: { action: "cpe_peru_getCliente", tipoDocumento: tipoDocumento, nroDocumento: nroDocumento },
        success: function (response) {
          console.log(response);
          if (response.success) {
            $('#wooweb_cpe_razonsocial').val(response.data.name);
            $('#wooweb_cpe_domiciliofiscal').val(response.data.address);
            $('#wooweb_cpe_ubigeo').val(response.data.district_id);
          } else {
            $('#wooweb_cpe_razonsocial').val('No se encontró el '.nombreDocumento);
            $('#wooweb_cpe_domiciliofiscal').val('No se encontró el '.nombreDocumento);
          }
          $('label[for="wooweb_cpe_razonsocial"]').html('Nombre <abbr class="required" title="obligatorio">*</abbr>');
        }
      });

    }


  });
});