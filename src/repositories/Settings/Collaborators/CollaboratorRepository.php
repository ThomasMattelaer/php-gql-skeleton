<?php

namespace Vertuoza\Repositories\Settings\Collaborators;

use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;
use React\Promise\Promise;
use Vertuoza\Repositories\Database\QueryBuilder;
use Vertuoza\Repositories\Settings\Collaborators\Models\CollaboratorMapper;
use Vertuoza\Repositories\Settings\Collaborators\Models\CollaboratorModel;
use Vertuoza\Repositories\Settings\Collaborators\CollaboratorMutationData;

use function React\Async\async;

class CollaboratorRepository
{
    protected array $getByIdsDL = [];
    private QueryBuilder $db;
    protected PromiseAdapterInterface $dataLoaderPromiseAdapter;

    public function __construct(
        QueryBuilder $database,
        PromiseAdapterInterface $dataLoaderPromiseAdapter
    ) {
        $this->db = $database;
        $this->dataLoaderPromiseAdapter = $dataLoaderPromiseAdapter;
        $this->getByIdsDL = [];
    }

    private function fetchByIds(string $tenantId, array $ids)
    {
        return async(function () use ($tenantId, $ids) {
            $query = $this->getQueryBuilder()
                ->where(function ($query) use ($tenantId) {
                    $query->where([CollaboratorModel::getTenantColumnName() => $tenantId])
                        ->orWhereNull(CollaboratorModel::getTenantColumnName());
                });
            $query->whereNull('deleted_at');
            $query->whereIn(CollaboratorModel::getPkColumnName(), $ids);

            $rows = $query->get();

            $entities = $rows->mapWithKeys(function ($row) {
                $model = CollaboratorModel::fromStdclass($row);
                $entity = CollaboratorMapper::modelToEntity($model);
                return [$entity->id => $entity];
            });

            // Préserve l’ordre des IDs
            return collect($ids)
                ->map(fn ($id) => $entities->get($id))
                ->toArray();
        })();
    }

    protected function getDataloader(string $tenantId): DataLoader
    {
        if (!isset($this->getByIdsDL[$tenantId])) {
            $dl = new DataLoader(function (array $ids) use ($tenantId) {
                return $this->fetchByIds($tenantId, $ids);
            }, $this->dataLoaderPromiseAdapter);

            $this->getByIdsDL[$tenantId] = $dl;
        }

        return $this->getByIdsDL[$tenantId];
    }

    protected function getQueryBuilder()
    {
        return $this->db->getConnection()->table(CollaboratorModel::getTableName());
    }

    public function getByIds(array $ids, string $tenantId): Promise
    {
        return $this->getDataloader($tenantId)->loadMany($ids);
    }

    public function getById(string $id, string $tenantId): Promise
    {
        return $this->getDataloader($tenantId)->load($id);
    }

    public function countCollaboratorWithLabel(string $label, string $tenantId, string|int|null $excludeId = null)
    {
        return async(function () use ($label, $tenantId, $excludeId) {
            $query = $this->getQueryBuilder()
                ->where('label', $label)
                ->whereNull('deleted_at');

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            $query->where(function ($query) use ($tenantId) {
                $query->where(CollaboratorModel::getTenantColumnName(), '=', $tenantId)
                    ->orWhereNull(CollaboratorModel::getTenantColumnName());
            });

            return $query->count();
        })();
    }

    public function findMany(string $tenantId)
    {
        return async(function () use ($tenantId) {
            $rows = $this->getQueryBuilder()
                ->whereNull('deleted_at')
                ->where(function ($query) use ($tenantId) {
                    $query->where(CollaboratorModel::getTenantColumnName(), '=', $tenantId)
                        ->orWhereNull(CollaboratorModel::getTenantColumnName());
                })
                ->get();

            return $rows->map(function ($row) {
                $model = CollaboratorModel::fromStdclass($row);
                return CollaboratorMapper::modelToEntity($model);
            });
        })();
    }

    public function create(CollaboratorMutationData $data, string $tenantId): int|string
    {
        return $this->getQueryBuilder()->insertGetId(
            CollaboratorMapper::serializeCreate($data, $tenantId)
        );
    }

    public function update(string $id, CollaboratorMutationData $data): void
    {
        $this->getQueryBuilder()
            ->where(CollaboratorModel::getPkColumnName(), $id)
            ->update(CollaboratorMapper::serializeUpdate($data));

        $this->clearCache($id);
    }

    private function clearCache(string $id): void
    {
        foreach ($this->getByIdsDL as $dl) {
            if ($dl->key_exists($id)) {
                $dl->clear($id);
                return;
            }
        }
    }
}