<?php

declare(strict_types=1);

namespace Polis\Repositories\Messaging;

use App\Models\Messaging\Message;
use App\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Polis\Contracts\Models\CanReceiveTextMessagesContract;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Models\BaseModelAbstract;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Repositories\Traits\NotImplemented\Delete;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Class MessageRepository
 */
class MessageRepository extends BaseRepositoryAbstract implements MessageRepositoryContract
{
    use Delete;

    private UserRepositoryContract $userRepository;

    /**
     * MessageRepository constructor.
     */
    public function __construct(Message $model, LogContract $log, UserRepositoryContract $userRepository)
    {
        parent::__construct($model, $log);
        $this->userRepository = $userRepository;
    }

    /**
     * Overrides to make sure to use the related model for the to field
     *
     * @param  User|BaseModelAbstract|null  $relatedModel
     * @return BaseModelAbstract
     */
    public function create(array $data = [], ?BaseModelAbstract $relatedModel = null, array $forcedValues = []): BaseModelAbstract
    {
        if ($relatedModel) {
            $data['to_id'] = $relatedModel->id;
        }

        return parent::create($data, null, $forcedValues);
    }

    /**
     * Sends an email directly to a user
     *
     * @return Message|BaseModelAbstract
     */
    public function sendDirectEmail(string $email, string $subject, string $template, string $greeting, array $baseTemplateData = []): Message
    {
        return $this->create([
            'subject' => $subject,
            'template' => $template,
            'email' => $email,
            'data' => array_merge($baseTemplateData, [
                'greeting' => $greeting,
            ]),
        ]);
    }

    /**
     * Find all
     *
     * @param  int|null  $limit  pass null to get all
     * @param  array  $belongsToArray  array of models this should belong to
     * @return LengthAwarePaginator|Collection
     */
    public function findAll(array $filters = [], array $searches = [], array $orderBy = [], array $with = [], $limit = 10, array $belongsToArray = [], int $pageNumber = 1): LengthAwarePaginator|Collection
    {
        $query = $this->buildFindAllQuery($filters, $searches, $orderBy, $with, $belongsToArray);

        $query->orderBy('created_at', 'desc');

        if ($limit) {
            return $query->paginate($limit, $columns = ['*'], $pageName = 'page', $pageNumber);
        }

        return $query->get();
    }

    /**
     * Sends an email directly to a user
     *
     * @param  null  $greeting
     * @return Message|BaseModelAbstract
     */
    public function sendEmailToUser(
        User $user,
        string $subject,
        string $template,
        array $baseTemplateData = [],
        ?string $greeting = null,
        array $via = [Message::VIA_EMAIL],
    ): Message {
        return $this->create([
            'subject' => $subject,
            'template' => $template,
            'email' => $user->email,
            'via' => $via,
            'data' => array_merge($baseTemplateData, [
                'greeting' => $greeting ?? 'Hello '.$user->first_name,
            ]),
        ], $user);
    }

    /**
     * Sends an email directly to the main system users in the system
     */
    public function sendEmailToSuperAdmins(
        string $subject,
        string $template,
        array $baseTemplateData = [],
        ?string $greeting = null,
        array $via = [Message::VIA_EMAIL],
    ): Collection {
        $messages = new Collection;

        foreach ($this->userRepository->findSuperAdmins() as $user) {
            $messages->push(
                $this->sendEmailToUser($user, $subject, $template, $baseTemplateData, $greeting, $via)
            );
        }

        return $messages;
    }

    /**
     * Sends a text message to a related model
     *
     * @return BaseModelAbstract|Message
     */
    public function sendTextMessage(CanReceiveTextMessagesContract $model, string $message): Message
    {
        return $this->create([
            'to_id' => $model->id,
            'to_type' => $model->morphRelationName(),
            'via' => [Message::VIA_SMS],
            'data' => [
                'message' => $message,
            ],
        ]);
    }
}
