<?php

namespace App\Dtos;

class MpSearchDto
{
    protected array $results;
    protected int $total;
    protected int $limit;
    protected int $offset;

    public function __construct(array $response)
    {
        $results = [];
        foreach ($response['results'] as $item) {
            $results[] = new MpResultDto($item);
        }

        $this->results = $results;
        $this->total = $response['paging']['total'];
        $this->limit = $response['paging']['limit'];
        $this->offset = $response['paging']['offset'];
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
