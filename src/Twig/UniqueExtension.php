<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UniqueExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('unique', [$this, 'uniqueFilter']),
        ];
    }

    public function uniqueFilter(array $array, ?string $property = null): array
    {
        if ($property) {
            // Pour les objets, filtrer par propriété
            $seen = [];
            $result = [];
            
            foreach ($array as $item) {
                if (is_object($item) && method_exists($item, 'get' . ucfirst($property))) {
                    $value = call_user_func([$item, 'get' . ucfirst($property)]);
                } elseif (is_object($item) && property_exists($item, $property)) {
                    $value = $item->$property;
                } elseif (is_array($item) && isset($item[$property])) {
                    $value = $item[$property];
                } else {
                    $value = (string) $item;
                }
                
                if (!in_array($value, $seen, true)) {
                    $seen[] = $value;
                    $result[] = $item;
                }
            }
            
            return $result;
        }
        
        // Pour les valeurs simples
        return array_values(array_unique($array, SORT_REGULAR));
    }
}