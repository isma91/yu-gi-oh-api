<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: "The User entity."
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    use SoftDeleteableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_list", "user_admin_list", "user_admin_info"])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups([
        "user_login",
        "user_list",
        "deck_user_list",
        "deck_info",
        "card_info",
        "user_basic_info",
        "collection_user_list",
        "collection_info",
        "user_admin_list",
        "user_admin_info"
    ])]
    private ?string $username = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[OA\Property(
        description: "All Deck of the User",
        type: "array",
        items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: "#/components/schemas/UserBasicDeckInfo"),
            ]
        ),
    )]
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Deck::class)]
    #[Groups(["user_basic_info"])]
    private Collection $decks;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CardCollection::class)]
    #[Groups(["user_basic_info"])]
    private Collection $cardCollections;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserToken::class)]
    #[Groups(["user_admin_info"])]
    private Collection $userTokens;

    public function __construct()
    {
        $this->decks = new ArrayCollection();
        $this->cardCollections = new ArrayCollection();
        $this->userTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        /**
         * ROLE_ADMIN => Who can manage User
         * ROLE_USER  => Basic User
         */
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addAdminRole(): static
    {
        $roleAdmin = "ROLE_ADMIN";
        $rolesArray = $this->roles;
        if (in_array($roleAdmin, $rolesArray, TRUE) === FALSE) {
            $rolesArray[] = $roleAdmin;
        }
        $this->roles = $rolesArray;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    #[Groups(["user_list", "user_admin_list"])]
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    #[Groups(["user_list", "user_admin_list"])]
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    public function addDeck(Deck $deck): static
    {
        if (!$this->decks->contains($deck)) {
            $this->decks->add($deck);
            $deck->setUser($this);
        }

        return $this;
    }

    public function removeDeck(Deck $deck): static
    {
        if ($this->decks->removeElement($deck)) {
            // set the owning side to null (unless already changed)
            if ($deck->getUser() === $this) {
                $deck->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CardCollection>
     */
    public function getCardCollections(): Collection
    {
        return $this->cardCollections;
    }

    public function addCardCollection(CardCollection $cardCollection): static
    {
        if (!$this->cardCollections->contains($cardCollection)) {
            $this->cardCollections->add($cardCollection);
            $cardCollection->setUser($this);
        }

        return $this;
    }

    public function removeCardCollection(CardCollection $cardCollection): static
    {
        if ($this->cardCollections->removeElement($cardCollection)) {
            // set the owning side to null (unless already changed)
            if ($cardCollection->getUser() === $this) {
                $cardCollection->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserToken>
     */
    public function getUserTokens(): Collection
    {
        return $this->userTokens;
    }

    public function addUserToken(UserToken $userToken): static
    {
        if (!$this->userTokens->contains($userToken)) {
            $this->userTokens->add($userToken);
            $userToken->setUser($this);
        }

        return $this;
    }

    public function removeUserToken(UserToken $userToken): static
    {
        if ($this->userTokens->removeElement($userToken)) {
            // set the owning side to null (unless already changed)
            if ($userToken->getUser() === $this) {
                $userToken->setUser(null);
            }
        }

        return $this;
    }
}
