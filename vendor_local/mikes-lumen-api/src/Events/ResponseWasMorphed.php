<?php

namespace MikesLumenApi\Events;

use Dingo\Api\Event\ResponseWasMorphed as DingoResponseWasMorphed;

class ResponseWasMorphed
{
    public function handle(DingoResponseWasMorphed $event)
    {
        $event->response->headers->set(
            'Access-Control-Allow-Origin',
            getenv('ALLOW_ORIGIN') ? getenv('ALLOW_ORIGIN') : '*'
        );
        $event->response->headers->set(
            'Access-Control-Allow-Credentials',
            getenv('ALLOW_CREDENTIALS') ? getenv('ALLOW_CREDENTIALS') : 'true'
        );
        $event->response->headers->set(
            'Access-Control-Allow-Methods',
            getenv('ALLOW_METHODS') ? getenv('ALLOW_METHODS') : 'OPTIONS, GET, POST, PUT, DELETE'
        );
        $event->response->headers->set(
            'Access-Control-Allow-Headers',
            getenv('ALLOW_HEADERS') ? getenv('ALLOW_HEADERS') : 'Authorization, X-Requested-With, Content-Type, Accept'
        );
        $event->response->headers->set(
            'Access-Control-Max-Age',
            getenv('MAX_AGE') ? getenv('MAX_AGE') : '600'
        );
        $event->response->headers->set('Cache-Control', 'no-cache');

        $exposedHeaders = [];
        if (isset($event->content['meta']['pagination'])) {
            array_push($exposedHeaders, 'X-Pagination-Total-Count', 'X-Pagination-Page-Count', 'X-Pagination-Current-Page', 'X-Pagination-Per-Page');

            $pagination = $event->content['meta']['pagination'];

            // $pagination has total, count, per_page, current_page, total_pages, links
            $event->response->headers->set('X-Pagination-Total-Count', $pagination['total']);
            $event->response->headers->set('X-Pagination-Page-Count', $pagination['total_pages']);
            $event->response->headers->set('X-Pagination-Current-Page', $pagination['current_page']);
            $event->response->headers->set('X-Pagination-Per-Page', $pagination['per_page']);

            /*
            $links = $pagination['links'];
            if (!empty($links)) {
                $event->response->headers->set(
                    'X-Pagination-Links',
                    sprintf('<%s>; rel="next", <%s>; rel="prev"', isset($links['next']) ? $links['next'] : '', isset($links['previous']) ? $links['previous'] : '')
                );
            }
            */
        }

        if (count($exposedHeaders) > 0) {
            $event->response->headers->set('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
        }

        if (isset($event->content['data'])) {
            $event->content = $event->content['data'];
        }
    }
}
