<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserDashboard extends CI_Controller {

	function __construct(){
        parent::__construct();
        $this->load->model('User_model');
        include APPPATH . 'third_party/AESCryptFileLib.php';
        include APPPATH . 'third_party/aes256/MCryptAES256Implementation.php';
        
    }
	public function index()
	{
        $email = $this->session->userdata('email');
        if ($email != '') {
            $data['title'] = 'User Dashboard';
            $data['userData'] = $this->User_model->UserData($email);
            $data['count_files'] = $this->User_model->count_files($email);
            $data['files'] =$this->User_model->fetch_upload_data($email);
            $this->load->view('user/dashboard',$data);
        }else {
            redirect('Login/','refresh');
        }
	}
	public function Upload()
    {
        $email = $this->session->userdata('email');
        if ($email != '') {
            $email = $this->session->userdata('email');
            $data['title'] = 'File Upload';
            $data['userData'] = $this->User_model->UserData($email);
            $data['count_files'] = $this->User_model->count_files($email);
            
            $this->load->view('user/upload',$data);
        }else {
            redirect('Login/','refresh');
        }
    }
    
    public function do_upload()
    {
        $email = $this->session->userdata('email');
        $dir_name ='./uploads/'.$email;

        if (!is_dir($dir_name)) {
        //Create our directory if it does not exist
        mkdir("./uploads/".$email);
        }
        // exit();
        if ($email != '') {
            $config['upload_path']='./uploads/'.$email;
            $config['allowed_types']='gif|jpg|png|docx|pdf|doc|xlx|xlsx';
            $config['encrypt_name'] = TRUE;
            $config['max_size'] = 99999999999;
            $config['file_name'] = date("d-m-Y").'_'.rand();
            $this->load->library('upload',$config);
            if($this->upload->do_upload("fileToUpload")){
                $dataupload = $this->upload->data();
                echo $file_name = $dataupload['file_name'];
                $file_ext = $dataupload['file_ext'];
                $full_path = $dataupload['full_path'];
                $mcrypt = new MCryptAES256Implementation();
                $lib = new AESCryptFileLib($mcrypt);
                $data = array('upload_data' => $this->upload->data());
                // echo '<pre>';
                // print_r($data['upload_data']);
                // exit();
                $ufile_name= $this->input->post('file_name');
                $file_pin = $this->input->post('file_pin');
                echo $encrypted_file = $file_name .".aes";
                @unlink($encrypted_file);
                $lib->encryptFile($full_path, $file_pin, $encrypted_file);

                // $image= $data['upload_data']['orig_name'];

                $data = array(
                    'user_email'    => $email,
                    'file_name'     => $file_name,
                    // 'file_ext'     => $file_ext,
                    // 'ufile_name'     => $ufile_name,
                    // 'full_path'     => $full_path,
                    'file_pin'      => $file_pin
                ); 

                $result= $this->User_model->save_upload($data);
                // echo json_decode($result);
                if($result){
                    $this->session->set_flashdata('success', 'User data have been added successfully.');
                }else{
                    $this->session->set_flashdata('error', 'Some problems occured, please try again.');
                }
                redirect('UserDashboard/Upload');
            }else{
                echo "error";
            }
        }else {
            redirect('Login/','refresh');
        }
    }
    public function download()
    {
        $email = $this->session->userdata('email');
        if ($email !='') {
            $id = $this->input->post('id');
            $pin = $this->input->post('pin');
            $data = $this->User_model->download_file($id,$pin);
            // echo '<pre>';
            // print_r($data);
            if($data){
                $mcrypt = new MCryptAES256Implementation();
                $lib = new AESCryptFileLib($mcrypt);
                $file_data = base_url()."uploads/".$data['user_email']."/".$data['file_name'];
                
                // echo $data = $lib->decryptFile($file_data, $pin);
                // exit();
                // $file_path = file_get_contents($file_data,$data['file_name']);
                // $name = $data['ufile_name'];
                // // exit();
                // force_download($name,$file_path);
                
                $data = array(
                    'status'=> 'true',
                    'file' => $file_data
                );
                echo json_encode($data);
                // $this->testdownload();
            }else{
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                $randomString = '';
                for ($i = 0; $i < 500; $i++) {
                    $randomString .= $characters[rand(0, $charactersLength - 1)];
                }
                
                $data = array(
                    'status'=> 'false',
                    'file' => $randomString
                );
                echo json_encode($data);
            }
        }else {
            redirect('Login/','refresh');
        }
    }

    public function testdownload()
    {
        $data = $this->User_model->download_file($id,$pin);
        echo $file_data = base_url()."uploads/".$data['user_email']."/".$data['file_name'];
        $data = file_get_contents($file_data);
        echo "<script language=\"javascript\">alert('test');</script>";
        force_download('test_file.pdf',$data);
    }
}