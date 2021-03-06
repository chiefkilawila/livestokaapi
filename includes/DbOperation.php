<?php
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';
//Load Composer's autoloader
//require 'vendor/autoload.php';


class DbOperation
{
    //Database connection link
    private $con;

    //Class constructor
    public function __construct()
    {
        //Getting the DbConnect.php file
        require_once dirname(__FILE__) . '/DbConnect.php';

        //Creating a DbConnect object to connect to the database
        $db = new DbConnect();

        //Initializing our connection link of this class
        //by calling the method connect of DbConnect class
        $this->con = $db->connect();
    }



    /////////////////////////REGISTRATION: Manufactures//////////////////////////////////////////////////////////////////////////

    /*
    *REGISTRATION: activation :
    * user account activation
    */
    public function activateUserAccount($user_id,$account_status)
    {

        $stmt = $this->con->prepare("UPDATE lvusers_tb SET account_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $account_status, $user_id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }




    public function activateUserAccountByEmail($user_id)
    {
        $account_status=1;
        $stmt = $this->con->prepare("UPDATE lvusers_tb SET account_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $account_status, $user_id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /*check if account is active*/

    public function isAccountActive($user_id, $account_status)
    {
        $stmt = $this->con->prepare("SELECT user_id, account_status FROM lvusers_tb WHERE user_id = ? AND account_status = ?");
        $stmt->bind_param("is", $user_id, $account_status);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // user existed
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }



 /*
    *REGISTRATION: type of user :
    * reg user type -> Manufactures

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
*/
    public function registerUser($email, $password, $usertype, $account_status)
    {
        $uuid = uniqid('', true);
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
     $salt = $hash["salt"]; // salt

     $stmt = $this->con->prepare("INSERT INTO lvusers_tb(user_unique_id, email, encrypted_password, usertype, salt, account_status, created_at) VALUES(?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $uuid, $email, $encrypted_password, $usertype, $salt, $account_status);

        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM lvusers_tb WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
          //  smtpmailer($email, $uuid);
            return $user;
        } else {
            echo mysql_error();

            return false;
        }
    }


    /*
    *add new manufacturer to the database
    */
    public function registerFeedManufacturers($user_id, $companyname, $year_established, $cert_of_incorporation_num, $feedbussiness_permit_num, $premise_cert_num, $gmp_cert_num, $country, $region, $district, $address, $pobox, $websiteurl, $contact_person, $production_capacity, $storage_capacity, $num_products_produced, $man_power, $plant_manager)
    {
        $mfuid = uniqid('', true);

        $stmt = $this->con->prepare("INSERT INTO feed_manufactures (feed_manufactures_unique_id, user_id, companyname, year_established, cert_of_incorporation_num, feedbussiness_permit_num, premise_cert_num, gmp_cert_num, country, region, district, address, pobox, websiteurl, contact_person, production_capacity, storage_capacity, num_products_produced, man_power, plant_manager, created_at)
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssssssssssssssss", $mfuid, $user_id, $companyname, $year_established, $cert_of_incorporation_num, $feedbussiness_permit_num, $premise_cert_num, $gmp_cert_num, $country, $region, $district, $address, $pobox, $websiteurl, $contact_person, $production_capacity, $storage_capacity, $num_products_produced, $man_power, $plant_manager);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM feed_manufactures WHERE cert_of_incorporation_num = ?");
            $stmt->bind_param("s", $cert_of_incorporation_num);
            $stmt->execute();
            $feed_manufacturer = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $feed_manufacturer;
        } else {
            return false;
        }
    }





    /**
     *
     *feed manufacturers: add a new product
     *
     */
    public function manufactureAddNewProduct($creator_id, $feed_manu_id, $product_name, $brand_name, $product_type, $protein_level, $product_purpose_statement, $product_description)
    {
        $npuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO feeds_products (feeds_products_unique_id, creator_id, manufacturers_id,  product_name, brand_name, product_type, protein_level, product_purpose_statement, product_description,  created_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssss", $npuid, $creator_id, $feed_manu_id, $product_name, $brand_name, $product_type, $protein_level, $product_purpose_statement, $product_description);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM feeds_products WHERE brand_name = ?");
            $stmt->bind_param("s", $brand_name);
            $stmt->execute();
            $feeds_product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $feeds_product;
        } else {
            return false;
        }
    }

    /**
     *
     *feed manufacturers: feed manufacturers get all products
     *
     */
    public function manufacturerGetAllProducts($manufacturers_id)
    {
        $stmt = $this->con->prepare("SELECT feed_product_id, feeds_products_unique_id, creator_id, manufacturers_id, product_name, brand_name, product_type, protein_level, product_purpose_statement, product_description, created_at, updated_at  FROM  feeds_products WHERE manufacturers_id = ?");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        $stmt->bind_param("s", $manufacturers_id);
        $stmt->execute();
        $stmt->bind_result($feed_product_id, $feeds_products_unique_id, $creator_id, $manufacturers_id, $product_name, $brand_name, $product_type, $protein_level, $product_purpose_statement, $product_description, $created_at, $updated_at);

        $products = array();

        while ($stmt->fetch()) {
            $product  = array();
            $product['feed_product_id'] = $feed_product_id;
            $product['feeds_products_unique_id'] = $feeds_products_unique_id;
            $product['creator_id'] = $creator_id;
            $product['manufacturers_id'] = $manufacturers_id;
            $product['product_name'] = $product_name;
            $product['brand_name'] = $brand_name;
            $product['product_type'] = $product_type;
            $product['protein_level'] = $protein_level;
            $product['product_purpose_statement'] = $product_purpose_statement;
            $product['product_description'] = $product_description;
            $product['created_at'] = $created_at;
            $product['updated_at'] = $updated_at;

            array_push($products, $product);
        }
        return $products;
    }
    /**
     *
     *feed manufacturers: feed manufacturers get all products production
     *
     */
    public function manufacturerGetAllProductProduction($manufacturers_id)
    {
        $stmt = $this->con->prepare("SELECT products_production_id, feed_production_unique_id, feeds_product_id, creator_id,  manufacturer_id, quantity_produced, quantity_measurements, current_quantity_instorage, instorage_measurements, product_manu_capacity, next_day_update, date_created, date_updated FROM  feeds_products_production WHERE manufacturer_id = $manufacturers_id");
        $stmt->execute();
        $stmt->bind_result($products_production_id, $feed_production_unique_id, $feeds_product_id, $creator_id, $manufacturer_id, $quantity_produced, $quantity_measurements, $current_quantity_instorage, $instorage_measurements, $product_manu_capacity, $next_day_update, $date_created, $date_updated);

        $product_productions = array();

        while ($stmt->fetch()) {
            $product_production  = array();
            $product_production['feed_production_unique_id'] = $feed_production_unique_id;
            $product_production['products_production_id'] = $products_production_id;
            $product_production['feeds_product_id'] = $feeds_product_id;
            $product_production['creator_id'] = $creator_id;
            $product_production['manufacturer_id'] = $manufacturer_id;
            $product_production['quantity_produced'] = $quantity_produced;
            $product_production['quantity_measurements'] = $quantity_measurements;
            $product_production['current_quantity_instorage'] = $current_quantity_instorage;
            $product_production['instorage_measurements'] = $instorage_measurements;
            $product_production['product_manu_capacity'] = $product_manu_capacity;
            $product_production['next_day_update'] = $next_day_update;
            $product_production['date_created'] = $date_created;
            $product_production['date_updated'] = $date_updated;

            array_push($product_productions, $product_production);
        }
        return $product_productions;
    }


    /*
    *FEED MANUFACTURER: get production with product name
    *
    */
    public function getProductionByManufacturer($manufacturer_id)
    {
        $stmt = $this->con->prepare("SELECT feeds_products_production.products_production_id
    , feeds_products.product_name
    , feeds_products.product_type
    , feeds_products_production.manufacturer_id
    , feeds_products_production.quantity_produced
    , feeds_products_production.quantity_measurements
    , feeds_products_production.current_quantity_instorage
	  , feeds_products_production.instorage_measurements
    , feeds_products_production.next_day_update
    , feeds_products_production.date_created

    FROM feeds_products_production
    INNER JOIN feeds_products
    ON
    feeds_products.feed_product_id=feeds_products_production.feeds_product_id WHERE  feeds_products_production.manufacturer_id = $manufacturer_id");
        $stmt->execute();
        $stmt->bind_result($products_production_id, $product_name, $product_type, $manufacturer_id, $quantity_produced, $quantity_measurements, $current_quantity_instorage, $instorage_measurements, $next_day_update, $date_created);

        $product_productions = array();

        while ($stmt->fetch()) {
            $product_production  = array();
            $product_production['products_production_id'] = $products_production_id;
            $product_production['product_name'] = $product_name;
            $product_production['product_type'] = $product_type;
            $product_production['manufacturer_id'] = $manufacturer_id;
            $product_production['quantity_produced'] = $quantity_produced;
            $product_production['quantity_measurements'] = $quantity_measurements;
            $product_production['current_quantity_instorage'] = $current_quantity_instorage;
            $product_production['instorage_measurements'] = $instorage_measurements;
            $product_production['next_day_update'] = $next_day_update;
            $product_production['date_created'] = $date_created;
            array_push($product_productions, $product_production);
        }
        return $product_productions;
    }



    /**
     *
     *feed manufacturers: add a product production
     *
     */
    public function manufactureProductProduction($feeds_product_id, $pcreator_id, $quantity_produced, $quantity_measurements, $current_quantity_instorage, $instorage_measurements, $next_day_update)
    {
        $fppuid = uniqid('', true);

        $stmt = $this->con->prepare("INSERT INTO feeds_products_production (feed_production_unique_id, feeds_product_id, creator_id,  quantity_produced, quantity_measurements, current_quantity_instorage, instorage_measurements, next_day_update, date_created) VALUES(?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssss", $fppuid, $feeds_product_id, $pcreator_id, $quantity_produced, $quantity_measurements, $current_quantity_instorage, $instorage_measurements, $next_day_update);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM feeds_products_production WHERE feeds_product_id = ?");
            $stmt->bind_param("s", $feeds_product_id);
            $stmt->execute();
            $product_production = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $product_production;
        } else {
            return false;
        }
    }

    public function getFeedManufacturerDetails($user_id)
    {
        $stmt = $this->con->prepare("SELECT feed_manufactures_id, feed_manufactures_unique_id, user_id, companyname, year_established, cert_of_incorporation_num, feedbussiness_permit_num, premise_cert_num, gmp_cert_num, association_affiliation, country, region, district, address, pobox, phonenumber, websiteurl, contact_person, production_capacity, storage_capacity, num_products_produced, man_power, plant_manager, created_at, updated_at FROM feed_manufactures WHERE user_id = ?");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");

        $stmt->bind_param("s", $user_id);

        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $manufacturer = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $manufacturer;
        } else {
            return null;
        }
    }


    /*
    *
    * feed manufacturer: adds new raw material they use
    */
    public function manuAddNewRawMaterial($creator_id, $manufacturer_id, $raw_material_title, $raw_material_desc, $purpose_statement)
    {
        $rmuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO raw_materials (raw_material_unique_id, creator_id, manufacturer_id, raw_material_title, raw_material_desc, purpose_statement, date_created) VALUES(?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $rmuid, $creator_id, $manufacturer_id, $raw_material_title, $raw_material_desc, $purpose_statement);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM raw_materials WHERE manufacturer_id = ?");
            $stmt->bind_param("s", $manufacturer_id);
            $stmt->execute();
            $rawmaterial = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $rawmaterial;
        } else {
            return false;
        }
    }

    /**
     *
     *feed manufacturers: feed manufacturers get all raw materials
     *
     */
    public function manufacturerGetAllRawMaterials($manufacturer_id)
    {
        $stmt = $this->con->prepare("SELECT raw_material_id, raw_material_unique_id, creator_id, manufacturer_id, raw_material_title, raw_material_desc, purpose_statement, date_created, date_updated  FROM raw_materials WHERE manufacturer_id = ?");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        $stmt->bind_param("s", $manufacturer_id);
        $stmt->execute();
        $stmt->bind_result($raw_material_id, $raw_material_unique_id, $creator_id, $manufacturer_id, $raw_material_title, $raw_material_desc, $purpose_statement, $date_created, $date_updated);

        $rawmaterials = array();

        while ($stmt->fetch()) {
            $rawmaterial  = array();
            $rawmaterial['raw_material_id'] = $raw_material_id;
            $rawmaterial['raw_material_unique_id'] = $raw_material_unique_id;
            $rawmaterial['creator_id'] = $creator_id;
            $rawmaterial['manufacturer_id'] = $manufacturer_id;
            $rawmaterial['raw_material_title'] = $raw_material_title;
            $rawmaterial['raw_material_desc'] = $raw_material_desc;
            $rawmaterial['purpose_statement'] = $purpose_statement;
            $rawmaterial['date_created'] = $date_created;
            $rawmaterial['date_updated'] = $date_updated;

            array_push($rawmaterials, $rawmaterial);
        }
        return $rawmaterials;
    }


    /*
    *FEED MANUFACTURER: get production with product name
    *
    */

    public function getRawMaterialByManufacturer($feed_manufacturer_id)
    {
        $stmt = $this->con->prepare("SELECT
 fm_rm_consumption.consumption_id
    , raw_materials.raw_material_title
    , fm_rm_consumption.feed_manufacturer_id
    , fm_rm_consumption.total_raw_materials_consumed
    , fm_rm_consumption.measurements
    , fm_rm_consumption.current_instorage
	  , fm_rm_consumption.instorage_measurement
    , fm_rm_consumption.next_day_update
    , fm_rm_consumption.date_created

FROM fm_rm_consumption
INNER JOIN  raw_materials
 ON
  raw_materials.raw_material_id=fm_rm_consumption.raw_material_id WHERE  fm_rm_consumption.feed_manufacturer_id = $feed_manufacturer_id");
        $stmt->execute();
        $stmt->bind_result($consumption_id, $raw_material_title, $feed_manufacturer_id, $total_raw_materials_consumed, $measurements, $current_instorage, $instorage_measurement, $next_day_update, $date_created);

        $consumptions = array();

        while ($stmt->fetch()) {
            $consumption  = array();
            $consumption['consumption_id'] = $consumption_id;
            $consumption['raw_material_title'] = $raw_material_title;
            $consumption['feed_manufacturer_id'] = $feed_manufacturer_id;
            $consumption['total_raw_materials_consumed'] = $total_raw_materials_consumed;
            $consumption['measurements'] = $measurements;
            $consumption['current_instorage'] = $current_instorage;
            $consumption['instorage_measurement'] = $instorage_measurement;
            $consumption['next_day_update'] = $next_day_update;
            $consumption['date_created'] = $date_created;
            array_push($consumptions, $consumption);
        }
        return $consumptions;
    }


    /*
    *
    * feed manufacturer: insert a raw material consumption
    */
    public function manuRawMaterialConsumption($feed_manufacturer_id, $raw_material_id, $creator_id, $use_of_material, $total_raw_materials_consumed, $measurements, $current_instorage, $instorage_measurement, $material_source, $next_day_update)
    {
        $rmcuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO fm_rm_consumption (consumption_unique_id, feed_manufacturer_id, raw_material_id, creator_id,  use_of_material, total_raw_materials_consumed, measurements, current_instorage, instorage_measurement, material_source, next_day_update, date_created) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssssss", $rmcuid, $feed_manufacturer_id, $raw_material_id, $creator_id, $use_of_material, $total_raw_materials_consumed, $measurements, $current_instorage, $instorage_measurement, $material_source, $next_day_update);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM fm_rm_consumption WHERE feed_manufacturer_id = ?");
            $stmt->bind_param("s", $feed_manufacturer_id);
            $stmt->execute();
            $rawmaterialconsumption = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $rawmaterialconsumption;
        } else {
            return false;
        }
    }

    /*
    *
    * feed manufacturer: insert a monthly industry manufacturing update
    */
    public function feedManufacturerIndustryData($feeds_manufacturer_id, $creator_id, $total_man_power, $total_products_produced, $measurements, $current_stock_instorage, $storage_qty_measurements, $num_raw_materials_used, $raw_materials_consumed, $current_raw_materials_instorage, $raw_materials_measurements, $next_day_update)
    {
        $miduid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO feeds_manufacturing_industry (operation_unique_id, feeds_manufacturer_id, creator_id, total_man_power, total_products_produced, measurements, current_stock_instorage, storage_qty_measurements, number_of_rawmaterials_used, raw_materials_consumed, current_raw_materials_instorage, raw_materials_measurements, next_day_update, date_created) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssssssss", $miduid, $feeds_manufacturer_id, $creator_id, $total_man_power, $total_products_produced, $measurements, $current_stock_instorage, $storage_qty_measurements, $num_raw_materials_used, $raw_materials_consumed, $current_raw_materials_instorage, $raw_materials_measurements, $next_day_update);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM feeds_manufacturing_industry WHERE feeds_manufacturer_id = ?");
            $stmt->bind_param("s", $feeds_manufacturer_id);
            $stmt->execute();
            $manuoperation = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $manuoperation;
        } else {
            return false;
        }
    }


    /*
    *FEED Manufacturer: get industry data
    *
    */
    public function manuGetIndustryProduction($feeds_manufacturer_id)
    {
        $stmt = $this->con->prepare("SELECT manufacturing_operation_id, operation_unique_id, feeds_manufacturer_id,
   creator_id, total_man_power, total_products_produced, measurements, current_stock_instorage,
   storage_qty_measurements, number_of_rawmaterials_used, raw_materials_consumed, current_raw_materials_instorage,
   raw_materials_measurements, next_day_update, date_created, date_updated FROM livestoka.feeds_manufacturing_industry WHERE feeds_manufacturer_id = $feeds_manufacturer_id");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        //$stmt->bind_param("s", $feeds_manufacturer_id);
        $stmt->execute();
        $stmt->bind_result(
      $manufacturing_operation_id,
      $operation_unique_id,
      $feeds_manufacturer_id,
   $creator_id,
      $total_man_power,
      $total_products_produced,
      $measurements,
      $current_stock_instorage,
   $storage_qty_measurements,
      $number_of_rawmaterials_used,
      $raw_materials_consumed,
      $current_raw_materials_instorage,
   $raw_materials_measurements,
      $next_day_update,
      $date_created,
      $date_updated
  );

        $industryops = array();

        while ($stmt->fetch()) {
            $industryop  = array();
            $industryop['manufacturing_operation_id'] = $manufacturing_operation_id;
            $industryop['operation_unique_id'] = $operation_unique_id;
            $industryop['feeds_manufacturer_id'] = $feeds_manufacturer_id;
            $industryop['creator_id'] = $creator_id;
            $industryop['total_man_power'] = $total_man_power;
            $industryop['total_products_produced'] = $total_products_produced;
            $industryop['measurements'] = $measurements;
            $industryop['current_stock_instorage'] = $current_stock_instorage;
            $industryop['storage_qty_measurements'] = $storage_qty_measurements;
            $industryop['number_of_rawmaterials_used'] = $number_of_rawmaterials_used;
            $industryop['raw_materials_consumed'] = $raw_materials_consumed;
            $industryop['current_raw_materials_instorage'] = $current_raw_materials_instorage;
            $industryop['raw_materials_measurements'] = $raw_materials_measurements;
            $industryop['next_day_update'] = $next_day_update;
            $industryop['date_created'] = $date_created;
            $industryop['date_updated'] = $date_updated;
            array_push($industryops, $industryop);
        }
        return $industryops;
    }



    /*
    *
    *FEED MANUFACTURER: GET INDUSTRY SUMMATIONS
    *
    */
    public function getManufactureringIndustrySummations($feeds_manufacturer_id)
    {
        $stmt = $this->con->prepare(" SELECT SUM(total_products_produced),SUM(current_stock_instorage),SUM(raw_materials_consumed), SUM(current_raw_materials_instorage), SUM(total_man_power)
 FROM feeds_manufacturing_industry WHERE feeds_manufacturer_id = $feeds_manufacturer_id");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        //$stmt->bind_param("s", $feeds_manufacturer_id);
        $stmt->execute();
        $stmt->bind_result(
      $total_products_produced,
      $current_stock_instorage,
      $raw_materials_consumed,
   $current_raw_materials_instorage,
      $total_man_power
  );

        $indsums = array();

        while ($stmt->fetch()) {
            $indsum  = array();
            $indsum['total_products_produced'] = $total_products_produced;
            $indsum['current_stock_instorage'] = $current_stock_instorage;
            $indsum['raw_materials_consumed'] = $raw_materials_consumed;
            $indsum['current_raw_materials_instorage'] = $current_raw_materials_instorage;
            $indsum['total_man_power'] = $total_man_power;
            array_push($indsums, $indsum);
        }
        return $indsums;
    }



    /*
    *
    *FEED MANUFACTURER: PRODUCT SUMMATIONS
    *
    */
    // public function getManufacturerProductSum($manufacturers_id){
    //   // $stmt = $this->con->prepare("SELECT COUNT(*) FROM feeds_products WHERE manufacturers_id = $manufacturers_id");
    //   // //$stmt->bind_param('i', $manufacturers_id);
    //   //   // $stmt->bind_param('i', $manufacturers_id);
    //   //   // $productsum = $stmt->get_result();
    //   //   $productsum = $stmt->execute();
    //   //   $stmt->close();
//
//     $troopcount_sql = ("SELECT COUNT(*) FROM feeds_products WHERE manufacturers_id = $manufacturers_id");
    // $productsum = $this->con->mysqli_query($troopcount_sql);
//
    // }


    /*
    *
    *FEED MANUFACTURER: RAW MATERIAL SUMMATIONS
    *
    */
    public function getManufacturerRawMaterialSum($manufacturer_id)
    {
    }







    /*
add new beef farm
*/
public function registerNewMilkDairy(
                    $user_id,
                    $dairy_farm_name,
                    $year_established,
                    $reg_number,
                    $dairy_farm_owner,
                    $affiliation,
                    $country,
                    $region,
                    $district,
                    $websiteurl,
                    $address,
                    $contact_person,
                    $total_litre_perday ,
                    $maximum_flock_size,
                    $dairy_manager,
                    $dairy_veterinarian,
                    $vet_reg_number,
                    $phone_Number,
                    $typeofbreeds
) {
        $dfuid = uniqid('', true);

        $stmt = $this->con->prepare("INSERT INTO dairy_tbl
            (       dairy_unique_id,
                    user_id,
                    dairy_farm_name,
                    year_established,
                    reg_number,
                    dairy_farm_owner,
                    country,
                    region,
                    district,
                    websiteurl,
                    address,
                    contact_person,
                    total_litre_perday ,
                    maximum_flock_size,
                    dairy_manager,
                    dairy_veterinarian,
                    vet_reg_number,
                    phone_Number,
                    typeofbreeds)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,NOW())");
        $stmt->bind_param("sssssssssssssssssss",
                    $dfuid,
                    $user_id,
                    $dairy_farm_name,
                    $year_established,
                    $reg_number,
                    $dairy_farm_owner,
                    $affiliation,
                    $country,
                    $region,
                    $district,
                    $websiteurl,
                    $address,
                    $contact_person,
                    $total_litre_perday ,
                    $maximum_flock_size,
                    $dairy_manager,
                    $dairy_veterinarian,
                    $vet_reg_number,
                    $typeofbreeds,
                    $phone_Number,
                    $created_at
            );
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM beef_tbl WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $beef = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $beef;
        } else {
            return false;
        }

    }

/*
add new beef farm
*/
public function registerNewBeefFarm(
                $user_id,
                $beef_farm_name,
                $year_established,
                $reg_number,
                $owners_full_name,
                $affiliation,
                $country,
                $region,
                $district,
                $pobox,
                $websiteurl,
                $address,
                $typeofbeef
) {
        $bfuid = uniqid('', true);

        $stmt = $this->con->prepare("INSERT INTO beef_tbl
            (breeder_unique_id,
                bfuid,
                user_id,
                beef_farm_name,
                year_established,
                reg_number,
                owners_full_name,
                country,
                region,
                district,
                pobox,
                websiteurl,
                address,
                created_at)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
                "sssssssssssssssssss",
                $bfuid,
                $user_id,
                $beef_farm_name,
                $year_established,
                $reg_number,
                $owners_full_name,
                $country,
                $region,
                $district,
                $pobox,
                $websiteurl,
                $address,
                $created_at);
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM beef_tbl WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $beef = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $beef;
        } else {
            return false;
        }

    }

    /*
    *add new breeder
    */
    public function registerNewBreeder(
      $user_id,
       $owners_full_name,
       $farm_name,
       $type_of_ownership,
       $date_established,
       $breed_reg_number,
       $breeder_manager,
       $breeder_veterinarian,
       $vet_reg_number,
       $maximum_flock_size,
       $total_peryear_capacity,
       $contact_person,
       $country,
       $region,
       $district,
       $pobox,
       $websiteurl,
       $address

    ) {
            $bffuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO breeders_tbl
                (breeder_unique_id,
                user_id,
                owners_full_name,
                farm_name,
                type_of_ownership,
                date_established,
                breeder_reg_number,
                breeder_manager,
                breeder_veterinarian,
                vet_reg_number,
                maximum_flock_size,
                  total_peryear_capacity,
                  contact_person,
                country,
                region,
                district,
                pobox,
                websiteurl,
                address,
                created_at)VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssssssssssssssssss",
                    $bffuid,
                    $user_id,
                     $owners_full_name,
                     $farm_name,
                     $type_of_ownership,
                     $date_established,
                     $breed_reg_number,
                     $breeder_manager,
                     $breeder_veterinarian,
                     $vet_reg_number,
                     $maximum_flock_size,
                     $total_peryear_capacity,
                     $contact_person,
                     $country,
                     $region,
                     $district,
                     $pobox,
                     $websiteurl,
                     $address
                );
            $result = $stmt->execute();
            $stmt->close();

            // check for successful store
            if ($result) {
                $stmt = $this->con->prepare("SELECT * FROM breeders_tbl WHERE breeder_unique_id = ?");
                $stmt->bind_param("s", $bffuid);
                $stmt->execute();
                $breeder = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                return $breeder;
            } else {
                return false;
            }

        }




              /**
               * get breeder farm details
               */
              public function getBreederFarmMainDetails($user_id){
                $stmt = $this->con->prepare("SELECT
                     breeders_id, breeder_unique_id,
                    user_id, farm_name,
                    date_established, type_of_ownership,
                    maximum_flock_size,
                    total_peryear_capacity,
                    contact_person,
                    created_at,
                    updated_at FROM breeders_tbl WHERE user_id = ?");
                //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {
                    // $user = $stmt->get_result()->fetch_assoc();
                    $breeder_farm = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    return $breeder_farm;
                } else {
                    return null;
                }

              }




     /*
       *insert multiple parent stock
       */
      public function lsp_stock($user_id, $breeder_id,    $local_source_parent_stock){
        //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
        //mysql_query($sql1);
        foreach(   $local_source_parent_stock as $a => $B){
        $affuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO lsp_stock (lsp_unique_id, user_id,
                                    breeder_id, local_source_parent_stock, created_at)
                                    VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $lsp_stockid, $user_id,
                                    $breeder_id,    $local_source_parent_stock[$a]);
        $result = $stmt->execute();
        $stmt->close();

        // if ($result){
        //   echo "affiliation insert success";
        // }
        }
        // check for successful store
        }
/*
       *insert multiple parent stock
       */
      public function lsgp_stock($user_id, $breeder_id,    $local_source_grandparent_stock){
        //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
        //mysql_query($sql1);
        foreach(   $local_source_grandparent_stock as $a => $B){
        $affuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO lsgp_stock (lsgp_unique_id, user_id,
                                    breeder_id, local_source_grandparent_stock, created_at)
                                    VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $lsgp_stockid, $user_id,
                                    $breeder_id,    $local_source_grandparent_stock[$a]);
        $result = $stmt->execute();
        $stmt->close();

        // if ($result){
        //   echo "affiliation insert success";
        // }
        }
        // check for successful store
        }
/*
       *insert multiple parent stock
       */
      public function isp_stock($user_id, $breeder_id,    $import_source_parent_stock){
        //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
        //mysql_query($sql1);
        foreach(   $local_source_parent_stock as $a => $B){
        $affuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO isp_stock (isp_unique_id, user_id,
                                    breeder_id, import_source_parent_stock, created_at)
                                    VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $isp_stockid, $user_id,
                                    $breeder_id,    $import_source_parent_stock[$a]);
        $result = $stmt->execute();
        $stmt->close();

        // if ($result){
        //   echo "affiliation insert success";
        // }
        }
        // check for successful store
        }
/*
       *insert multiple parent stock
       */
      public function isgp_stock($user_id, $breeder_id,    $import_source_grandparent_stock){
        //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
        //mysql_query($sql1);
        foreach(   $local_source_parent_stock as $a => $B){
        $affuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO isgp_stock (isgp_unique_id, user_id,
                                    breeder_id, import_source_grandparent_stock, created_at)
                                    VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $isgp_stockid, $user_id,
                                    $breeder_id,    $import_source_grandparent_stock[$a]);
        $result = $stmt->execute();
        $stmt->close();

        // if ($result){

        //   echo "affiliation insert success";
        // }
        }
        // check for successful store
        }

       /*
       *insert multiple Affiliations
       */
        public function Affiliations($user_id, $breeder_id, $affiliation){
            //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
            //mysql_query($sql1);
            foreach($affiliation as $a => $B){
            $affuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO affiliation (aff_unique_id, user_id,
                                        breeder_id, affiliation, created_at)
                                        VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $affuid, $user_id,
                                        $breeder_id, $affiliation[$a]);
            $result = $stmt->execute();
            $stmt->close();

            // if ($result){
            //   echo "affiliation insert success";
            // }
            }
            // check for successful store
            }

        /*
        *insert multiple phonenumbers
        */
         public function PhoneNumbers($user_id, $breeder_id, $phone_Number){
           foreach($phoneNumber as $a => $B){
           $phnuid = uniqid('', true);
           $stmt = $this->con->prepare("INSERT INTO phonenumbers (phonenum_unique_id, user_id,
                                       breeder_id, phonenumber, created_at)VALUES(?, ?, ?, ?, NOW())");
           $stmt->bind_param("ssss", $phnuid, $user_id, $breeder_id, $phoneNumber[$a]);
           $result = $stmt->execute();
           $stmt->close();
         //   if ($result){
         //     echo "phoneNumber insert success";
         //   }
          }
           // check for successful store
         }


         /*
         *insert multiple typeofBreed
         */
          public function TypeOfBeef($user_id, $bfuid, $typeofbeef){
            //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
            //mysql_query($sql1);
            foreach($typeofbeef as $a => $B){
            $tpuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO  TypeOfDairyproduct (breed_unique_id, user_id,
     bfuid, breed_title, created_at)VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $tpuid, $user_id, $bfuid, $typeofbeef[$a]);
            $result = $stmt->execute();
            $stmt->close();

             // if ($result){
             //   echo "Breed Type insert success";
             // }
              }
            // check for successful store
          }

          public function TypeOfBreedProduced($user_id, $breeder_id, $typeofbreed){
            //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
            //mysql_query($sql1);
            foreach($typeofbreed as $a => $B){
            $tpuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO breeder_breeds (breed_unique_id, user_id,
     breeder_id, breed_title, created_at)VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $tpuid, $user_id, $breeder_id, $typeofbreed[$a]);
            $result = $stmt->execute();
            $stmt->close();

             // if ($result){
             //   echo "Breed Type insert success";
             // }
              }
            // check for successful store
          }

          /*
          *insert multiple breeder products
          */
           public function breederProducts($user_id, $breeder_id, $hatched_products){

             $hpuid = uniqid('', true);
             $stmt = $this->con->prepare("INSERT INTO hatchery_products (breeder_product_unique_id, user_id,
      breeder_id, breeder_products, created_at)VALUES(?, ?, ?, ?, NOW())");
             $stmt->bind_param("ssss", $hpuid, $user_id, $breeder_id, $hatched_products);
             $result = $stmt->execute();
             $stmt->close();

            //   }
             // check for successful store
           }

           /*
           *insert multiple breeder breed purpose
           */
            public function breederPurpose($user_id, $breeder_id, $breed_purpose){
              $hppuid = uniqid('', true);
              $stmt = $this->con->prepare("INSERT INTO hatching_purpose (hatching_purpose_unique_id, user_id,
      breeder_id,  purpose, created_at)VALUES(?, ?, ?, ?, NOW())");
              $stmt->bind_param("ssss", $hppuid, $user_id, $breeder_id, $breed_purpose);
              $result = $stmt->execute();
              $stmt->close();

             //   }
              // check for successful store
            }


            /*
            *insert multiple breeder poultry types
            */
             public function regbreederPoultryTypes($user_id, $breeder_id, $poultry_type){
               $hptuid = uniqid('', true);
               $stmt = $this->con->prepare("INSERT INTO hatching_poultry_types (poultry_types_unique_id, user_id,
      breeder_id,  hatchery_poultry_type, created_at)VALUES(?, ?, ?, ?, NOW())");
               $stmt->bind_param("ssss", $hptuid, $user_id, $breeder_id, $poultry_type);
               $result = $stmt->execute();
               $stmt->close();

             }

              /*
              *insert multiple flock sources types
              */
               public function regbreederFlockSources($user_id, $breeder_id, $flock_sources){
                 $eguid = uniqid('', true);
                 $stmt = $this->con->prepare("INSERT INTO  breeder_flock_source (source_unique_id, user_id,
       breeder_id,  breeder_flock_source, date_created)VALUES(?, ?, ?, ?, NOW())");
                 $stmt->bind_param("ssss", $eguid, $user_id, $breeder_id, $flock_sources);
                 $result = $stmt->execute();
                 $stmt->close();

               }




    /*
    *add new hatchery
    */
    public function registerNewHatchery(
    $user_id,
    $owners_full_name,
    $hatchery_name,
    $type_of_ownership,
    $date_established,
    $hatch_reg_number,
    $hatchery_manager,
    $hatchery_veterinarian,
    $vet_reg_number,
    $total_incubator_capacity,
    $total_hatcher_capacity,
    $contact_person,
    $country,
    $region,
    $district,
    $pobox,
    $websiteurl,
    $address
) {
        $htuid = uniqid('', true);

        $stmt = $this->con->prepare("INSERT INTO hatcheries_tbl
(hatchery_unique_id, user_id, owners_full_name, hatchery_name, type_of_ownership, date_established,
hatch_reg_number, hatchery_manager,
hatchery_veterinarian, vet_reg_number, total_incubator_capacity, total_hatcher_capacity, contact_person, country,
region, district, pobox, websiteurl, address, created_at)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param(
                "sssssssssssssssssss",
                $htuid,
                $user_id,
                $owners_full_name,
                $hatchery_name,
                $type_of_ownership,
                $date_established,
                $hatch_reg_number,
                $hatchery_manager,
                $hatchery_veterinarian,
                $vet_reg_number,
                $total_incubator_capacity,
                $total_hatcher_capacity,
                $contact_person,
                $country,
                $region,
                $district,
                $pobox,
                $websiteurl,
                $address
            );
        $result = $stmt->execute();
        $stmt->close();

        // check for successful store
        if ($result) {
            $stmt = $this->con->prepare("SELECT * FROM hatcheries_tbl WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $hatchery = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $hatchery;
        } else {
            return false;
        }
    }

   /*
   *insert multiple Affiliations
   */
    public function multipleAffiliations($user_id, $hatchery_id, $affiliation){
      //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
      //mysql_query($sql1);
      foreach($affiliation as $a => $B){
      $affuid = uniqid('', true);
      $stmt = $this->con->prepare("INSERT INTO affiliation (aff_unique_id, user_id,
                                 hatchery_id, affiliation, created_at)
                                 VALUES(?, ?, ?, ?, NOW())");
      $stmt->bind_param("ssss", $affuid, $user_id,
                                 $hatchery_id, $affiliation[$a]);
      $result = $stmt->execute();
      $stmt->close();

      // if ($result){
      //   echo "affiliation insert success";
      // }
    }
      // check for successful store
    }



    /*
    *breeeder insert multiple Affiliations
    */
     public function multipleBreederAffiliations($user_id, $breeder_id, $affiliation){
       //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
       //mysql_query($sql1);
       foreach($affiliation as $a => $B){
       $affuid = uniqid('', true);
       $stmt = $this->con->prepare("INSERT INTO affiliation (aff_unique_id, user_id,
                                  breeder_id, affiliation, created_at)
                                  VALUES(?, ?, ?, ?, NOW())");
       $stmt->bind_param("ssss", $affuid, $user_id,
                                  $breeder_id, $affiliation[$a]);
       $result = $stmt->execute();
       $stmt->close();

       // if ($result){
       //   echo "affiliation insert success";
       // }
     }
       // check for successful store
     }


        /*
        *insert multiple Dairy Affiliations
        */
         public function multipleDairyffiliations($user_id, $dairy_farm_id, $affiliation){
           //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
           //mysql_query($sql1);
           foreach($affiliation as $a => $B){
           $affuid = uniqid('', true);
           $stmt = $this->con->prepare("INSERT INTO affiliation (aff_unique_id, user_id,
                                       dairy_farm_id, affiliation, created_at)
                                      VALUES(?, ?, ?, ?, NOW())");
           $stmt->bind_param("ssss", $affuid, $user_id,
                                      $dairy_farm_id, $affiliation[$a]);
           $result = $stmt->execute();
           $stmt->close();

           // if ($result){
           //   echo "affiliation insert success";
           // }
         }
           // check for successful store
         }

         /*
         *insert multiple Feed Manufacturers Affiliations
         */
          public function multipleFeedManufacturersffiliations($user_id, $feed_manufactures_id, $association_affiliation){
            //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
            //mysql_query($sql1);
            foreach($association_affiliation as $a => $B){
            $affuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO affiliation (aff_unique_id, user_id,
                                        feed_manufacturers_id, affiliation, created_at)
                                       VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $affuid, $user_id,
                                       $feed_manufactures_id, $association_affiliation[$a]);
            $result = $stmt->execute();
            $stmt->close();

            // if ($result){
            //   echo "affiliation insert success";
            // }
          }
            // check for successful store
          }



    /*
    *insert multiple phonenumbers
    */
     public function multiplePhoneNumber($user_id, $hatchery_id, $phoneNumber){
       foreach($phoneNumber as $a => $B){
       $phnuid = uniqid('', true);
       $stmt = $this->con->prepare("INSERT INTO phonenumbers (phonenum_unique_id, user_id,
                                   hatchery_id, phonenumber, created_at)VALUES(?, ?, ?, ?, NOW())");
       $stmt->bind_param("ssss", $phnuid, $user_id, $hatchery_id, $phoneNumber[$a]);
       $result = $stmt->execute();
       $stmt->close();
     //   if ($result){
     //     echo "phoneNumber insert success";
     //   }
      }
       // check for successful store
     }


     /*
     *insert multiple phonenumbers
     */
      public function multipleBreederPhoneNumber($user_id, $breeder_id, $phoneNumber){
        foreach($phoneNumber as $a => $B){
        $phnuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO phonenumbers (phonenum_unique_id, user_id,
                                    breeder_id, phonenumber, created_at)VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $phnuid, $user_id, $breeder_id, $phoneNumber[$a]);
        $result = $stmt->execute();
        $stmt->close();
      //   if ($result){
      //     echo "phoneNumber insert success";
      //   }
       }
        // check for successful store
      }


          /*
          *insert multiple dairy phonenumbers
          */
           public function multipleFeedManufacturersPhoneNumbers($user_id, $feed_manufactures_id, $phonenumber){
             foreach($phonenumber as $a => $B){
             $phnuid = uniqid('', true);
             $stmt = $this->con->prepare("INSERT INTO phonenumbers (phonenum_unique_id, user_id,
                                         feed_manufactures_id, phonenumber, created_at)VALUES(?, ?, ?, ?, NOW())");
             $stmt->bind_param("ssss", $phnuid, $user_id, $feed_manufactures_id, $phonenumber[$a]);
             $result = $stmt->execute();
             $stmt->close();
           //   if ($result){
           //     echo "phoneNumber insert success";
           //   }
            }
             // check for successful store
           }




         /*
         *insert multiple feed manufacturers phonenumbers
         */
          public function multipleDairyPhoneNumbers($user_id, $dairy_farm_id, $phoneNumber){
            foreach($phoneNumber as $a => $B){
            $phnuid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO phonenumbers (phonenum_unique_id, user_id,
                                        dairy_farm_id, phonenumber, created_at)VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $phnuid, $user_id, $dairy_farm_id, $phoneNumber[$a]);
            $result = $stmt->execute();
            $stmt->close();
          //   if ($result){
          //     echo "phoneNumber insert success";
          //   }
           }
            // check for successful store
          }

      /*
      *insert multiple typeofBreed
      */
       public function multipleBreederTypeOfBreedProduced($user_id, $breeder_id, $typeofbreeds){
         //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
         //mysql_query($sql1);
         foreach($typeofbreeds as $a => $B){
         $tpuid = uniqid('', true);
         $stmt = $this->con->prepare("INSERT INTO hatchery_breeds (breed_unique_id, user_id,
  breeder_id, breed_title, created_at)VALUES(?, ?, ?, ?, NOW())");
         $stmt->bind_param("ssss", $tpuid, $user_id, $breeder_id, $typeofbreeds[$a]);
         $result = $stmt->execute();
         $stmt->close();

          // if ($result){
          //   echo "Breed Type insert success";
          // }
           }
         // check for successful store
       }



     /*
     *insert multiple typeofBreed
     */
      public function multipleTypeOfBreedProduced($user_id, $hatchery_id, $typeofbreeds){
        //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
        //mysql_query($sql1);
        foreach($typeofbreeds as $a => $B){
        $tpuid = uniqid('', true);
        $stmt = $this->con->prepare("INSERT INTO hatchery_breeds (breed_unique_id, user_id,
 hatchery_id, breed_title, created_at)VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $tpuid, $user_id, $hatchery_id, $typeofbreeds[$a]);
        $result = $stmt->execute();
        $stmt->close();

         // if ($result){
         //   echo "Breed Type insert success";
         // }
          }
        // check for successful store
      }



      /*
      *insert multiple typeofBreed
      */
       public function multipleDairyTypeOfBreeds($user_id, $dairy_farm_id, $typeofbreeds){
         //$sql1 = "insert into $lastname(item,price)values('$items[$a]','$prices[$a]')";
         //mysql_query($sql1);
         foreach($typeofbreeds as $a => $B){
         $tpuid = uniqid('', true);
         $stmt = $this->con->prepare("INSERT INTO hatchery_breeds (breed_unique_id, user_id,
     dairy_farm_id, breed_title, created_at)VALUES(?, ?, ?, ?, NOW())");
         $stmt->bind_param("ssss", $tpuid, $user_id, $dairy_farm_id, $typeofbreeds[$a]);
         $result = $stmt->execute();
         $stmt->close();

          // if ($result){
          //   echo "Breed Type insert success";
          // }
           }
         // check for successful store
       }


      /*
      *insert multiple hatchery products
      */
       public function multipleHatcheryProducts($user_id, $hatchery_id, $hatched_products){

         $hpuid = uniqid('', true);
         $stmt = $this->con->prepare("INSERT INTO hatchery_products (hatchery_product_unique_id, user_id,
  hatchery_id, hatched_products, created_at)VALUES(?, ?, ?, ?, NOW())");
         $stmt->bind_param("ssss", $hpuid, $user_id, $hatchery_id, $hatched_products);
         $result = $stmt->execute();
         $stmt->close();

        //   }
         // check for successful store
       }

       /*
       *insert multiple hatchery breed purpose
       */
        public function multipleHatcheryBreedPurpose($user_id, $hatchery_id, $breed_purpose){
          $hppuid = uniqid('', true);
          $stmt = $this->con->prepare("INSERT INTO hatching_purpose (hatching_purpose_unique_id, user_id,
  hatchery_id,  purpose, created_at)VALUES(?, ?, ?, ?, NOW())");
          $stmt->bind_param("ssss", $hppuid, $user_id, $hatchery_id, $breed_purpose);
          $result = $stmt->execute();
          $stmt->close();

         //   }
          // check for successful store
        }


        /*
        *insert multiple hatchery poultry types
        */
         public function regHatcheryPoultryTypes($user_id, $hatchery_id, $poultry_type){
           $hptuid = uniqid('', true);
           $stmt = $this->con->prepare("INSERT INTO hatching_poultry_types (poultry_types_unique_id, user_id,
  hatchery_id,  hatchery_poultry_type, created_at)VALUES(?, ?, ?, ?, NOW())");
           $stmt->bind_param("ssss", $hptuid, $user_id, $hatchery_id, $poultry_type);
           $result = $stmt->execute();
           $stmt->close();

         }


         /*
         *insert multiple egg sources types
         */
          public function regHatcheryEggSources($user_id, $hatchery_id, $egg_sources){
            $eguid = uniqid('', true);
            $stmt = $this->con->prepare("INSERT INTO hatchery_egg_sources (source_unique_id, user_id,
  hatchery_id,  eggs_source, date_created)VALUES(?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $eguid, $user_id, $hatchery_id, $egg_sources);
            $result = $stmt->execute();
            $stmt->close();

          }


      /**
       * get hatchery details
       */
      public function getHatcheryMainDetails($user_id){
        $stmt = $this->con->prepare("SELECT
            hatchery_id, hatchery_unique_id,
            user_id, hatchery_name,
            date_established, type_of_ownership,
            contact_person,
            phone_number,
            created_at,
            updated_at FROM hatcheries_tbl WHERE user_id = ?");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $hatchery = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $hatchery;
        } else {
            return null;
        }

      }


  /**
   * create a new batch
   */
   public function hatcheryCreateNewBatch($hatchery_id, $creator_id, $public_batch_id, $quantity_of_eggs, $date_recorded, $egg_source_name, $eggs_age, $number_of_breaks,
    $number_of_eggs_set, $date_set, $number_of_setters, $setting_temperature, $setting_humidity, $next_upcoming_update, $stage_one){
     $nbuid = uniqid('', true);
     $stmt = $this->con->prepare("INSERT INTO all_created_hatch_batches (batch_unique_id, hatchery_id, creator_id, public_batch_id,
 quantity_of_eggs, date_recorded, egg_source, age_of_eggs, number_of_breaks, number_of_eggs_on_setter,
 date_set, number_of_setters, setting_temperature, setting_humidity, stage_two_next_update, stage_one, date_created)
 VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
     $stmt->bind_param("ssssssssssssssss", $nbuid, $hatchery_id, $creator_id, $public_batch_id, $quantity_of_eggs, $date_recorded, $egg_source_name, $eggs_age, $number_of_breaks, $number_of_eggs_set, $date_set, $number_of_setters, $setting_temperature, $setting_humidity, $next_upcoming_update, $stage_one);
     $result = $stmt->execute();
     $stmt->close();

     // check for successful store
     if ($result) {
         $stmt = $this->con->prepare("SELECT * FROM all_created_hatch_batches WHERE public_batch_id = ?");
         $stmt->bind_param("s", $public_batch_id);
         $stmt->execute();
         $new_batch = $stmt->get_result()->fetch_assoc();
         $stmt->close();

         return $new_batch;
     } else {
         return false;
     }

   }

   /**
    * update created batch
    * hatchery
    * when batch is at stage 2
    */
     public function updateCreatedBatch(){
       $stmt = $this->con->prepare("UPDATE lvusers_tb SET account_status = ? WHERE user_id = ?");
       $stmt->bind_param("si", $account_status, $user_id);
       if ($stmt->execute()) {
           return true;
       }
       return false;

     }

     /**
      * create a new on-going batch
      *
      */
    public function createOngoingBatch($ongoing_creator_id, $ongoing_hatchery_id, $ongoing_public_batch,
$created_batch_id,  $ongoing_batch_current_stage, $ongoing_batch_status){
      $ogbuid = uniqid('', true);
      $stmt = $this->con->prepare("INSERT INTO ongoing_hatching_batches (unique_ongoing_batches, creator_id, hatchery_id, public_batch_id,
 created_batch_id, batch_current_stage, batch_status, date_created) VALUES(?, ?, ?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param("sssssss", $ogbuid, $ongoing_creator_id, $ongoing_hatchery_id, $ongoing_public_batch, $created_batch_id,  $ongoing_batch_current_stage, $ongoing_batch_status);
      $result = $stmt->execute();
      $stmt->close();

      // check for successful store
      if ($result) {
          $stmt = $this->con->prepare("SELECT * FROM ongoing_hatching_batches WHERE creator_id = ?");
          $stmt->bind_param("s", $created_batch_id);
          $stmt->execute();
          $new_batch = $stmt->get_result()->fetch_assoc();
          $stmt->close();

          return $new_batch;
      } else {
          return false;
      }
    }


    /*
    * hatchery
    * gets all hatchery batches
    */
    public function getAllHatcheryBatches($hatchery_id)
    {
        $stmt = $this->con->prepare("SELECT ongoing_batches_id, unique_ongoing_batches,
creator_id, hatchery_id, public_batch_id, created_batch_id,
 batch_current_stage, batch_status, date_created, date_updated FROM  ongoing_hatching_batches WHERE hatchery_id = ?");
        //$stmt = $this->con->prepare("SELECT * FROM businessDetails WHERE user_id = ?");
        $stmt->bind_param("s", $hatchery_id);
        $stmt->execute();
        $stmt->bind_result($ongoing_batches_id, $unique_ongoing_batches,
$creator_id, $hatchery_id, $public_batch_id, $created_batch_id,
 $batch_current_stage, $batch_status, $date_created, $date_updated);

        $allbatches = array();
        while ($stmt->fetch()) {
            $batch  = array();
            $batch['ongoing_batches_id'] = $ongoing_batches_id;
            $batch['unique_ongoing_batches'] = $unique_ongoing_batches;
            $batch['creator_id'] = $creator_id;
            $batch['hatchery_id'] = $hatchery_id;
            $batch['public_batch_id'] = $public_batch_id;
            $batch['created_batch_id'] = $created_batch_id;
            $batch['batch_current_stage'] = $batch_current_stage;
            $batch['batch_status'] = $batch_status;
            $batch['date_created'] = $date_created;
            $batch['date_updated'] = $date_updated;

            array_push($allbatches, $batch);
        }
        return $allbatches;
    }

    /*
    * hatchery
    * gets all hatchery specific batch details
    */
    public function getBatchDetails($public_batch_id)
    {
        $stmt = $this->con->prepare("SELECT ongoing_batches_id, unique_ongoing_batches,
creator_id, hatchery_id, public_batch_id, created_batch_id,
 batch_current_stage, batch_status, date_created, date_updated FROM  ongoing_hatching_batches WHERE public_batch_id = ?");

 $stmt->bind_param("s", $public_batch_id);
 if ($stmt->execute()) {
     // $user = $stmt->get_result()->fetch_assoc();
     $batchdetails = $stmt->get_result()->fetch_assoc();
     $stmt->close();
     return $batchdetails;
 } else {
     return null;
 }
    }


        /*
        *Hatchery; GETs all hatchery breeds produced
        *
        */
        public function getallHatcheryBreeds($hatchery_id)
        {
            $stmt = $this->con->prepare("SELECT hatchery_breed_id, breed_unique_id, user_id, hatchery_id, breed_title, created_at, updated_at FROM livestoka.hatchery_breeds WHERE hatchery_id = $hatchery_id");
            $stmt->execute();
            $stmt->bind_result(
          $hatchery_breed_id,
          $breed_unique_id,
          $user_id,
          $hatchery_id,
          $breed_title,
          $created_at,
          $updated_at
      );

            $breeds = array();

            while ($stmt->fetch()) {
                $breed  = array();
                $breed['hatchery_breed_id'] = $hatchery_breed_id;
                $breed['breed_unique_id'] = $breed_unique_id;
                $breed['user_id'] = $user_id;
                $breed['hatchery_id'] = $hatchery_id;
                $breed['breed_title'] = $breed_title;
                $breed['created_at'] = $created_at;
                $breed['updated_at'] = $updated_at;
                array_push($breeds, $breed);
            }
            return $breeds;
        }



        /**
         *DAIRY FARM ACTIVITIES
         */

   /**
    * Register new dairy farm
    */

public function registerNewDairyFarm(
                        $user_id, $dairy_farm_name, $date_established, $reg_number,
                        $dairy_farm_owner, $country,
                        $region, $district, $address, $contact_person, $total_litres_perday,
                        $dairy_farm_size, $cattle_quantity, $dairy_manager, $dairy_veterinarian, $vet_reg_number
                        ){
    $dfuid = uniqid('', true);
    $stmt = $this->con->prepare("
 INSERT INTO dairy_farms
    (dairy_farm_unique_id, user_id, dairy_farm_name, year_established, reg_number,
    dairy_farm_owner, country,
    region, district, address, contact_person, total_litres_perday,
    dairy_farm_size, cattle_quantity, dairy_manager, dairy_veterinarian, vet_reg_number, created_at)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param(
            "sssssssssssssssss",
            $dfuid,
            $user_id, $dairy_farm_name, $date_established, $reg_number,
            $dairy_farm_owner, $country,
            $region, $district, $address, $contact_person, $total_litres_perday,
            $dairy_farm_size, $cattle_quantity, $dairy_manager, $dairy_veterinarian, $vet_reg_number
        );
    $result = $stmt->execute();
    $stmt->close();

    // check for successful store
    if ($result) {
        $stmt = $this->con->prepare("SELECT * FROM dairy_farms WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $hatchery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $hatchery;
    } else {
        return false;
    }
}


    /**
     * Check user is existed or not
     */
    public function doesUserEmailExist($email)
    {
        $stmt = $this->con->prepare("SELECT email from lvusers_tb WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // user existed
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }


    /**
     * Check if manufacturer userid is existed or not
     */
    public function doesFeedManufactureExist($user_id)
    {
        $stmt = $this->con->prepare("SELECT user_id FROM feed_manufactures WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // user existed
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }



    /**
     * Check if manufacturer userid is existed or not
     */
    public function doesHatcheryUserExist($user_id)
    {
        $stmt = $this->con->prepare("SELECT user_id FROM hatcheries_tbl WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // user existed
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }

    /**
       * Encrypting password
       * @param password
       * returns salt and encrypted password
       */
    public function hashSSHA($password)
    {
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }

    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password)
    {
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
        return $hash;
    }


    /**
       * Get user by email and password
       */
    public function attempt_login($email, $password)
    {
        $stmt = $this->con->prepare("SELECT * FROM lvusers_tb WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // verifying user password
            $salt = $user['salt'];
            $encrypted_password = $user['encrypted_password'];
            $hash = $this->checkhashSSHA($salt, $password);
            // check for password equality
            if ($encrypted_password == $hash) {
                // user authentication details are correct
                return $user;
            }
        } else {
            return null;
        }
    }



    function smtpmailer($to,$id) {
        global $error;
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mailFrom = "info@livestoka.com";
        require '../vendor/autoload.php';
       $subject="Email Confirmation";
       $verificationLink = "http://livestoka.com/activate_page.php?id=". $id;
       $htmlStr = "";
       $htmlStr .= "Hi " . $to . ",<br /><br />";

       $htmlStr .= "Please click the button below to verify your subscription and verify your account <br /><br /><br />";
       $htmlStr .= "<a href='{$verificationLink}' target='_blank' style='padding:1em; font-weight:bold; background-color:blue; color:#fff;'>VERIFY EMAIL</a><br /><br /><br />";

       $htmlStr .= "Kind regards,<br />";
       $htmlStr .= "<a href='http://livestoka.com/' target='_blank'>Industry and Data analytics platform</a><br />";


       $body=$htmlStr;
          // create a new object
         $mail->isSMTP();
         $mail->SMTPDebug = 4;                               // Enable verbose debug output                                 // Set mailer to use SMTP
         $mail->Host = 'smtp.zoho.com'; // Specify main and backup SMTP servers
         $mail->Port = 465;
         $mail->SMTPAuth = true;                             // Enable SMTP authentication
         $mail->Username =  $mailFrom;    //'info@livestoka.com';           // SMTP username
         $mail->Password = 'dereckkev2bros';                       // SMTP password                                // TCP port to connect, tls=587, ssl=465
         $mail->setFrom = 'info@livestoka.com';
         $mail->FromName = 'Please Verify Account';
         $mail->addReplyTo('info@livestoka.com', 'future basics');
         $mail->addAddress($to);     // Add a recipient
         $mail->Subject = $subject;
         $mail->Body    = $body;
         $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
             // if( mail($recipient_email, $subject, $body, $headers) ){
        if($mail->send()){

            $message = "<div class=\"alert alert-success\" role=\"alert\">
           <strong>Well done!</strong> You successfully registered check you emails<a href=\"#\" class=\"alert-link\">to activate your account</a>.
            </div>";

           } else{

            $message = "<div class=\"alert alert-warning\" role=\"alert\">
            <strong>Sorry!</strong> we cant seem to verify you account<a href=\"#\" class=\"alert-link\">click here to resend.</a>.
           </div>";
            echo "Mail error:" . $mail->ErrorInfo;

            }

                }

    /*
* The delete operation
* When this method is called record is deleted for the given id
*/
    public function deleteUserAccount($user_id)
    {
        $stmt = $this->con->prepare("DELETE FROM lvusers_tb WHERE user_id = ? ");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>
