<?php

namespace Vertuoza\Api\Graphql\Resolvers\Settings\UnitTypes;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Vertuoza\Api\Graphql\Types;

class Collaborator extends ObjectType

{
  public function __construct()
  {
    parent::__construct([
      'name' => 'Collaborator', 
      'description' => 'Collaborator type', 
      'fields' => static function (): array {
        return [
          'id' => [
            'description' => 'Unique identifier of the collaborator', 
            'type' => Types::id()
          ], 
          "name" => [
            "description" => 'Name of the Collaborator', 
            "type" => Types::string()
          ], 
          "firstName" => [
            "description" => 'First name of the Collaborator', 
            "type" => Types::string()
          ]
          ]; 
      }
    ]); 
  }
}