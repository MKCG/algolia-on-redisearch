<?php

namespace App\Controller;

use App\Schema\IMDB;
use App\Schema\Siret;
use MKCG\Model\DBAL\QueryEngine;
use MKCG\Model\DBAL\QueryCriteria;
use MKCG\Model\DBAL\FilterInterface;
use MKCG\Model\DBAL\AggregationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    private $engine;

    public function __construct(QueryEngine $engine)
    {
        $this->engine = $engine;
    }

    public function index()
    {
        return $this->render('index.html.twig');
    }


    public function stores()
    {
        return $this->render('stores.html.twig');
    }

    public function singleIndexQuery(string $index, Request $request)
    {
        return new JsonResponse([
            'results' => [ [ 'params' => $request['raw'] ?? ''] + $this->getResult($index, []) ]
        ]);
    }

    public function manyIndexQuery(Request $request)
    {
        $requests = $this->getRequests();
        $results = [];

        foreach ($requests as $request) {
            $results[] = [ 'params' => $request['raw'] ?? '']
                + $this->getResult($request['indexName'], $request['params'] ?? []);
        }

        return new JsonResponse([ 'results' => $results ]);
    }

    private function getResult($index, $params)
    {
        foreach (['attributesToRetrieve', 'facets', 'facetFilters'] as $paramName) {
            if (isset($params[$paramName])) {
                $params[$paramName] = json_decode($params[$paramName], JSON_OBJECT_AS_ARRAY);
            }
        }

        $limit = $params['hitsPerPage'] ?? 10;
        $page = $params['page'] ?? 0;

        $model = null;
        $fulltextField = null;

        switch ($index) {
            case 'imdb_titles':
                $model = IMDB::make('default', $index);
                $fulltextField = 'primaryTitle';
                break;

            case 'siret':
                $model = Siret::make('default', $index);
                $fulltextField = 'all';
                break;
            
            default:
                throw new \Exception("Unknown index");
        }

        $criteria = new QueryCriteria();

        $criteria
            ->forCollection($index)
            ->setOffset($page * $limit)
            ->setLimit($limit);

        if (isset($params['query']) && is_string($params['query']) && $params['query'] !== '' && $fulltextField !== null) {
            $criteria->addFilter($fulltextField, FilterInterface::FILTER_FULLTEXT_MATCH, $params['query']);
        }

        $filtersIn = [];

        if (isset($params['facets']) && is_array($params['facets'])) {
            foreach ($params['facets'] as $facetName) {
                $criteria->addAggregation(AggregationInterface::FACET, [
                    'field' => $facetName,
                    'offset' => 0,
                    'limit' => $params['maxValuesPerFacet'] ?? 10
                ]);
            }
        }

        if (isset($params['facetFilters']) && is_array($params['facetFilters'])) {
            foreach ($params['facetFilters'] as $facetFilter) {
                foreach ($facetFilter as $selectedFacet) {
                    list($fieldName, $selectedValue) = explode(':', $selectedFacet);

                    if (!isset($filtersIn[$fieldName])) {
                        $filtersIn[$fieldName] = [];
                    }

                    $filtersIn[$fieldName][] = $selectedValue;
                }
            }
        }

        foreach ($filtersIn as $fieldName => $selectedValues) {
            $criteria->addFilter($fieldName, FilterInterface::FILTER_IN, $selectedValues);
        }

        $startedAt = microtime(true);
        $results = $this->engine->query($model, $criteria);
        $took = microtime(true) - $startedAt;

        $facets = [];
        $aggregations = $results->getAggregations();

        if (isset($aggregations['facets'])) {
            foreach ($aggregations['facets'] as $name => $values) {
                $facet = [];

                foreach ($values as $value) {
                    $facet[$value['name']] = $value['count'];
                }

                $facets[$name] = $facet;
            }
        }

        $hits = array_map(function($hit) {
            $hit['_highlightResult'] = [];
            return $hit;
        }, $results->getContent());

        if (isset($params['attributesToRetrieve']) && is_array($params['attributesToRetrieve'])) {
            $hits = array_map(function($hit) use ($params) {
                $hit = $hit->toArray();

                return array_filter($hit, function($field) use ($params) {
                    return $field === 'id' || in_array($field, $params['attributesToRetrieve']);
                }, ARRAY_FILTER_USE_KEY);
            }, $hits);
        }

        return [
            'exhaustiveFacetsCount' => true,
            'exhaustiveNbHits' => true,
            'facets' => $facets,
            'facets_stats' => [],
            'hits' => $hits,
            'hitsPerPage' => $limit,
            'index' => $index,
            'nbHits' => $results->getCount(),
            'nbPages' => ceil($results->getCount() / $limit),
            'page' => $page,
            'processingTimeMS' => round($took * 1000),
            'query' => $params['query'] ?? '',
        ];
    }

    private function getRequests()
    {
        $parameters = $this->interpretFormBodyAsJson();
        return !is_array($parameters) || !isset($parameters['requests'])
            ? []
            : array_map(function($request) {
                $params = [];

                foreach (explode('&', $request['params'] ?? []) as $param) {
                    list($name, $value) = explode('=', $param);
                    $name = urldecode($name);
                    $value = urldecode($value);
                    $params[$name] = $value;
                }

                return [
                    'indexName' => $request['indexName'],
                    'params' => $params,
                    'raw' => $request['params'],
                ];
            }, $parameters['requests']);
    }

    private function interpretFormBodyAsJson()
    {
        return json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);
    }
}
