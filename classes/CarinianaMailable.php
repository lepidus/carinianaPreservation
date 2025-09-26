<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use PKP\mail\Mailable;

class CarinianaMailable extends Mailable
{
    public function getData(?string $locale = null): array
    {
        if ($locale && isset($this->viewData[$locale])) {
            $value = $this->viewData[$locale];
            return is_array($value) ? $value : [$value];
        }
        return parent::getData($locale);
    }
}
