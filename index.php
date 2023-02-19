<?php

class Travel
{
    public function travelsCost() {
        $travels = file_get_contents('travels.json');
        $arrTravels = json_decode($travels, true);

        // handle group travels and cost
        $groupTravel = [];
        foreach ($arrTravels as ["companyId" => $companyId, "price" => $price]) {
            $groupTravel[$companyId]['id'] = $companyId;
            $groupTravel[$companyId]['cost'][] = floatval($price);
        }
        
        return array_values($groupTravel);
    }
}

class Company
{
    public function companies() {
        $companies = file_get_contents('companies.json');

        return json_decode($companies, true);
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);

        // get data
        $companies = (new Company())->companies();
        $travels = (new Travel())->travelsCost();

        // map data travel cost with company
        $flatData = [];
        foreach ($companies as $company) {
            foreach ($travels as ["id" => $id, "cost" => $cost]) {
                if ($company['id'] === $id) {
                    $company['cost'] = array_sum($cost);
                    array_push($flatData, $company);
                }
            }
        }

        // handle build tree and get sum
        $result = $this->buildTree($flatData);

        file_put_contents('result.json', json_encode($result));
        echo PHP_EOL.' Total time: '.  (microtime(true) - $start).PHP_EOL;
    }

    function buildTree(array $flat) {
        foreach ($flat as $item) {
            $keyed[$item['id']] = array_intersect_key(
                $item,
                array_flip(['id', 'name', 'cost'])
            );
        }
        foreach ($flat as ["id" => $id, "parentId" => $parentId]) {
            if (isset($keyed[$parentId])) {
                $keyed[$parentId]["children"][] = &$keyed[$id]; 
            } else {
                $root = &$keyed[$id];
            }
        }

        $this->updateSum($root);
        return $root;
    }

    function updateSum(&$node) {
        foreach ($node["children"] ?? [] as &$child) {
            $node["cost"] += $this->updateSum($child);
        }
        return $node["cost"];
    }
}

(new TestScript())->execute();
