<?php
// sla
namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Comment;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function store(Request $request) : JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'pdf' => 'required|file|mimes:pdf|max:100000', // Máximo de 100MB
            'cover_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Máximo de 2MB para imagem
        ]);

        // Armazenar o PDF
        $pdfPath = $request->file('pdf')->store('pdfs', 'public');

        // Armazenar a capa, se fornecida
        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('covers', 'public');
        }

        // Criar o livro
        $book = Book::create([
            'title' => $request->input('title'),
            'author' => $request->input('author'),
            'path' => $pdfPath,
            'cover_image' => $coverImagePath, // Adicionar o caminho da capa
        ]);

        return response()->json(['message' => 'Livro criado com sucesso!', 'book' => $book], 201);
    }

    public function index(Request $request) : JsonResponse
    {
        $perPage = $request->input('perPage', 10);
        $books = Book::paginate($perPage);
        return response()->json($books);
    }

    public function downloadPdf($id)
    {
        $book = Book::findOrFail($id);

        if (!$book->path) {
            return response()->json(['message' => 'PDF não encontrado.'], 404);
        }

        return response()->file(storage_path('app/public/' . $book->path));
    }

    public function bookPage($id) : JsonResponse
    {
        $book = Book::findOrFail($id);

        if (!$book) {
            return response()->json(['message' => 'Livro não encontrado.'], 404);
        }

        $comments = Comment::where('book_id', $id)->get();

        return response()->json([
            'book' => $book,
            'comments' => $comments
        ]);
    }

    public function addComment(Request $request, $bookId) : JsonResponse
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $book = Book::findOrFail($bookId);
        $comment = new Comment();
        $comment->user_id = auth()->user()->id;
        $comment->book_id = $book->id;
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json(['message' => 'Comentário adicionado com sucesso!']);
    }
}
