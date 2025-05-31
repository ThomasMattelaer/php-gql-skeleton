<?php

namespace Vertuoza\Repositories\Settings\UnitTypes\Models;

use stdClass;
use Vertuoza\Repositories\Settings\Collaborators\Models\CollaboratorModel;
use Vertuoza\Repositories\Settings\Collaborators\CollaboratorMutationData;
use Vertuoza\Entities\Settings\UnitTypeEntity;

class CollaboratorMapper
{
  public static function modelToEntity(CollaboratorModel $dbData): CollaboratorEntity
  {
    $entity = new CollaboratorEntity();
    $entity->id = $dbData->id . '';
    $entity->name = $dbData->label;
    $entity->email = $dbData->email;
    $entity->isActive = $dbData->is_active;

    return $entity;
  }

  public static function serializeUpdate(CollaboratorMutationData $mutation): array
  {
    return self::serializeMutation($mutation);
  }

  public static function serializeCreate(CollaboratorMutationData $mutation, string $tenantId): array
  {
    return self::serializeMutation($mutation, $tenantId);
  }

  private static function serializeMutation(CollaboratorMutationData $mutation, string $tenantId = null): array
  {
    $data = [
      'label' => $mutation->name,
    ];

    if ($tenantId) {
      $data[CollaboratorModel::getTenantColumnName()] = $tenantId;
    }
    return $data;
  }
}
