<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;
use App\Models\Ingredient;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Searcging recipes from DB
        $recipes = DB::table('recipes')
            ->select('recipes.id', 'recipes.name', 'recipes.description', 'recipes.stat_id')
            ->get()
            ->toArray();

        // Looping into recipes array
        if (!empty($recipes)) {
            foreach ($recipes as $i => $recipe) {
                
                // Initializing & creating api response 
                $response[] = (object) array(
                    "id" => $recipe->id,
                    "name" => $recipe->name,
                    "description" => $recipe->description,
                );
                
                // Searching ingredients from DB
                $ingredient_list = DB::table('ingredient_recipe')
                ->join('ingredients', 'ingredients.id', '=', 'ingredient_recipe.ingredient_id')
                ->select('ingredients.id as id', 'ingredients.name as name', 'ingredients.description as description', 'ingredient_recipe.recipe_id as recipe_id')
                ->where('recipe_id', $recipe->id)
                ->select('ingredients.id as id', 'ingredients.name as name', 'ingredients.description as description')
                ->get()
                ->toArray();
                
                // Searcging stats from DB
                $stat = DB::table('stats')
                    ->select('stats.id', 'stats.type', 'stats.points', 'stats.duration')
                    ->where('stats.id', $recipe->stat_id)
                    ->first();
    
                // Creating ingredient arrays & stat objects for all recipes 
                $response[$i]->ingredients = my_array_unique($ingredient_list);
                $response[$i]->recipe_effect = (object)$stat;
            }
            return response()->json($response); 
        } else {
            return []; 
        }

        // Return json array of recipes
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Set request payload to $data
        $data = $request->all();

        // Initialize Recipe model & set table cells
        $newRecipe = new Recipe;
        $newRecipe->name = $data['name'];
        $newRecipe->description = $data['description'];
        $newRecipe->stat_id = $data['stat_id'];
        
        // Sae to DB
        $newRecipe->save();
        
        // Adding ingredients to pivot table 
        $recipe = Recipe::find($newRecipe->id);
        foreach ($data['ingredient_ids'] as $ingredient) {
            $newRecipe->ingredients()->attach($ingredient);
        }

        return "suucess";
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Searcging recipes from DB
        $recipe = DB::table('recipes')
            ->select('recipes.id', 'recipes.name', 'recipes.description', 'recipes.stat_id')
            ->where('recipes.id', $id)
            ->first();
        
       // Initializing & creating api response 
       $response = (object) array(
            "id" => $recipe->id,
            "name" => $recipe->name,
            "description" => $recipe->description,
        );

        // Searching ingredients from DB
        $ingredient_list = DB::table('ingredient_recipe')
            ->join('ingredients', 'ingredients.id', '=', 'ingredient_recipe.ingredient_id')
            ->select('ingredients.id as id', 'ingredients.name as name', 'ingredients.description as description', 'ingredient_recipe.recipe_id as recipe_id')
            ->where('recipe_id', $id)
            ->select('ingredients.id as id', 'ingredients.name as name', 'ingredients.description as description')
            ->get()
            ->toArray();

        // Searcging stats from DB
        $stat = DB::table('stats')
            ->select('stats.id', 'stats.type', 'stats.points', 'stats.duration')
            ->where('stats.id', $recipe->stat_id)
            ->first();

        // Creating ingredient arrays & stat objects for all recipes 
        $response->ingredients = my_array_unique($ingredient_list);
        $response->recipe_effect = (object)$stat;

        // Return json of recipe
        return response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

 // Counting quantity of ingredients
 function count_array_values($my_array, $match) 
 { 
     $count = 0; 
     
     foreach ($my_array as $key => $value) 
     { 
         if ($value->id == $match) 
         { 
             $count++; 
         } 
     }
     
     return $count; 
 }

// Adding quantity value and remove duplicate objects
function my_array_unique($array, $keep_key_assoc = false){
    $duplicate_keys = array();
    $tmp = array();

    foreach ($array as $key => $val){
        // Adding quantity value ->
        $val->quantity = count_array_values($array, $val->id);

        // remove duplicate objects ->
        // convert objects to arrays, in_array() does not support objects
        if (is_object($val))
            $val = (array)$val;

        if (!in_array($val, $tmp))
            $tmp[] = $val;
        else
            $duplicate_keys[] = $key;
    }

    foreach ($duplicate_keys as $key)
        unset($array[$key]);

    return $keep_key_assoc ? $array : array_values($array);
}
