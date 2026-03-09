<?php
namespace App\Models;

class OfferModel extends Model {

    /**
     * OfferModel constructor.
     * 
     * @param mixed $connection The database connection. If null, a new FileDatabase connection will be created.
     */
    public function __construct($connection = null) {
        if(is_null($connection)) {
            $this->connection = new FileDatabase('offer', ['offer', 'status']);
        } else {
            $this->connection = $connection;
        }
    }

    /**
     * Get all offer from the model.
     * 
     * @return array An array of all offers.
     */
    public function getAllOffers() {
        return $this->connection->getAllRecords();
    }

    /**
     * Get a specific offer by its ID.
     * 
     * @param int $id The ID of the offer.
     * @return mixed The offer with the specified ID.
     */
    public function getOffer($id) {
        return $this->connection->getRecord($id);
    }
    

    /**
     * Add a new offer to the model.
     * 
     * @param string $offer The offer to add.
     * @return mixed The result of the insert operation.
     */
    public function addOffer($offer) {
        // Create a new record with the offer and the status 'todo' (by default)
    }

    /**
     * Helper method to update a offer with a new status.
     * Update a offer with a new offer and status.
     * 
     * @param int $id The ID of the offer to update.
     * @param string $offer The new offer.
     * @param string $status The new status.
     * @return mixed The result of the update operation.
     */
    private function updateoffer($id, $offer, $status) {

    }

}