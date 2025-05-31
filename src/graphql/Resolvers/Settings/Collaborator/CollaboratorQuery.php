<?php

namespace Vertuoza\Api\Graphql\Resolvers\Settings\ ;

use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use Vertuoza\Api\Graphql\Types;


class CollaboratorQuery
{
    static function get()
    {
        return [
            'collaboratorById' => [
                'type' => Types::get(Collaborator::class),
                'args' => [
                    'id' => new NonNull(Types::string()),
                ],
                'resolve' => static function ($rootValue, $args, RequestContext $context){
                    error_log(print_r($args, true));
                    return $context->useCases->collaborator
                        ->collaboratorById
                        ->handle($args['id'], $context);
                }
            ],
            'collaborators' => [
                'type' => new NonNull(new ListOfType(Types::get(Collaborator::class))),
                'resolve' => static function ($rootValue, $args, RequestContext $context){
                    return $context->useCases->collaborator
                        ->collaboratorsFindMany
                        ->handle($context);
                }
],
        ];
    }
}
