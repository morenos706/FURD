<?php

namespace App\Helpers;

use App\Models\Catalog;

class FormRenderer
{
    /** Renderiza un campo dinamico (fd[nombre]) segun su tipo XLSForm original. */
    public static function field(array $field, $value): string
    {
        $name = $field['name'];
        $id = 'fd_' . $name;
        $inputName = "fd[{$name}]" . ($field['type'] === 'select_multiple' ? '[]' : '');
        $label = Helpers::e($field['label']);
        $hint = $field['hint'] ? '<div class="form-text">' . Helpers::e($field['hint']) . '</div>' : '';

        $control = match ($field['type']) {
            'select_one' => self::selectOne($id, $inputName, $field['list'], $value),
            'select_multiple' => self::selectMultiple($id, $inputName, $field['list'], (array) $value),
            'decimal', 'integer', 'range' => sprintf(
                '<input type="number" step="any" class="form-control" id="%s" name="%s" value="%s">',
                $id, $inputName, Helpers::e((string) $value)
            ),
            'date' => sprintf('<input type="date" class="form-control" id="%s" name="%s" value="%s">', $id, $inputName, Helpers::e((string) $value)),
            'time' => sprintf('<input type="time" class="form-control" id="%s" name="%s" value="%s">', $id, $inputName, Helpers::e((string) $value)),
            'dateTime' => sprintf('<input type="datetime-local" class="form-control" id="%s" name="%s" value="%s">', $id, $inputName, Helpers::e((string) $value)),
            'geopoint' => sprintf('<input type="text" class="form-control" id="%s" name="%s" value="%s" placeholder="lat, lng">', $id, $inputName, Helpers::e((string) $value)),
            'image', 'file' => sprintf('<input type="text" class="form-control" id="%s" name="%s" value="%s" placeholder="Ruta / referencia del archivo adjunto">', $id, $inputName, Helpers::e((string) $value)),
            default => sprintf(
                (str_contains(strtolower($field['label']), 'descripcion') || str_contains(strtolower($field['label']), 'observ'))
                    ? '<textarea class="form-control" rows="3" id="%1$s" name="%2$s">%4$s</textarea>'
                    : '<input type="text" class="form-control" id="%1$s" name="%2$s" value="%3$s">',
                $id, $inputName, Helpers::e((string) $value), Helpers::e((string) $value)
            ),
        };

        return <<<HTML
        <div class="col-md-4 mb-3">
            <label for="{$id}" class="form-label small fw-semibold">{$label}</label>
            {$control}
            {$hint}
        </div>
        HTML;
    }

    private static function selectOne(string $id, string $name, ?string $list, $value): string
    {
        $options = $list ? Catalog::items($list) : [];
        $html = "<select class=\"form-select\" id=\"{$id}\" name=\"{$name}\"><option value=\"\">-- Seleccione --</option>";
        foreach ($options as $opt) {
            $selected = ((string) $value === $opt['item_value']) ? ' selected' : '';
            $html .= '<option value="' . Helpers::e($opt['item_value']) . '"' . $selected . '>' . Helpers::e($opt['item_label']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private static function selectMultiple(string $id, string $name, ?string $list, array $values): string
    {
        $options = $list ? Catalog::items($list) : [];
        $html = '<div class="border rounded p-2" style="max-height:160px; overflow-y:auto;" id="' . $id . '">';
        foreach ($options as $opt) {
            $checked = in_array($opt['item_value'], $values, true) ? ' checked' : '';
            $optId = $id . '_' . md5($opt['item_value']);
            $html .= '<div class="form-check"><input class="form-check-input" type="checkbox" name="' . $name . '" id="' . $optId . '" value="' . Helpers::e($opt['item_value']) . '"' . $checked . '>'
                   . '<label class="form-check-label small" for="' . $optId . '">' . Helpers::e($opt['item_label']) . '</label></div>';
        }
        $html .= '</div>';
        return $html;
    }
}
