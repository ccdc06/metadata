package LANraragi::Plugin::Metadata::SpecYAML;

use strict;
use warnings;

use Mojo::JSON qw(from_json);
use YAML::PP qw(LoadFile);
use File::Basename;

use LANraragi::Model::Plugins;
use LANraragi::Utils::Logging qw(get_plugin_logger);

#Meta-information about your plugin.
sub plugin_info {

    return (
        #Standard metadata
        name         => "SpecYAML",
        type         => "metadata",
        namespace    => "theplan",
        author       => "CCDC06",
        version      => "0.3",
        description  => "Gathers metadata from paired yaml files.<br/>
        Metadata file must have the same name as the archive file and be located in the same folder, replacing their extension with <code>.yaml</code>.<br/>
        Example implementation: https://github.com/ccdc06/metadata",
        icon         => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUBAMAAAB/pwA+AAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAJcEhZcwAACxMAAAsTAQCanBgAAAAnUExURUdwTJyta3OEUkJCMUpaQmNrhKWttc6tpUJCWv/n3oxra+fe53MpOfazgJQAAAABdFJOUwBA5thmAAAAk0lEQVQI12NgAAMmBwYYYEQwReAsJmE4S9EZzhKCMpUEBUVcDMBCgoKCQi7OQDaIBWS6AJWYgEVVXFyMGZhNnBQFlZTAwmY5VS5KSkogxdkx5TVnnI2BLLZt4TOnn6lKawCalho1c+bMit2pQCZr6Kryqo7d2wJA1nWs6ujYmgqx2sW5IQEsyMDiBDIJDJyUlEFMALXoIKIL0M1eAAAAAElFTkSuQmCC",
        parameters   => [ { type => "bool", desc => "Assume english" } ],
    );

}

sub get_tags {

    shift;
    my $lrr_info = shift;
    my ($assume_english) = @_;

    my $logger = get_plugin_logger();
    my $file   = $lrr_info->{file_path};

    my ( $name, $path, $suffix ) = fileparse( $lrr_info->{file_path}, qr/\.[^.]*/ );
    my $path_nearby_yaml = $path . $name . '.yaml';

    my $filepath;

    if ( -e $path_nearby_yaml ) {
        $filepath = $path_nearby_yaml;
        $logger->debug("Found file in the same folder at $filepath");
    } else {
        return ( error => "No SpecYAML metadata file found in the same folder" );
    }

    my $parsed_data = LoadFile($filepath);

    my ( $tags, $title, $summary ) = tags_from_yaml( $parsed_data, $assume_english );

    #Return tags
    $logger->info("Sending the following tags to LRR: $tags");
	if ($summary) {
		$logger->info("Parsed summary is $summary");
		if ($title) {
            $logger->info("Parsed title is $title");
            return ( tags => $tags, title => $title, summary => $summary );
        } else {
            return ( tags => $tags, summary => $summary );
        }
	}
    if ($title) {
        $logger->info("Parsed title is $title");
        return ( tags => $tags, title => $title );
    } else {
        return ( tags => $tags );
    }
}

sub tags_from_yaml {

    my $hash           = $_[0];
    my $assume_english = $_[1];
    my @found_tags;

    my $logger = get_plugin_logger();

    my $title     = $hash->{"Title"};
	my $summary   = $hash->{"Description"};
    my $artists   = $hash->{"Artist"};
    my $parodies  = $hash->{"Parody"};
    my $series    = $hash->{"Series"};
    my $urls      = $hash->{"URL"};
    my $tags      = $hash->{"Tags"};
    my $magazines = $hash->{"Magazine"};
    my $released  = $hash->{"Released"};

    foreach my $artist (@$artists) {
        push( @found_tags, "artist:" . $artist );
    }
    foreach my $parody (@$parodies) {
        push( @found_tags, "parody:" . $parody );
    }
    foreach my $serie (@$series) {
        push( @found_tags, "series:" . $serie );
    }
    foreach my $key (keys %{ $urls }) {
        push( @found_tags, "source:" . $urls->{$key} );
    }
    foreach my $tag (@$tags) {
        push( @found_tags, $tag );
    }
    foreach my $magazine (@$magazines) {
        push( @found_tags, "magazine:" . $magazine );
    }
    if ($assume_english) {
        push( @found_tags, "language:english" );
    }

    push( @found_tags, "timestamp:" . $released ) unless !$released;

    #Done-o
    my $concat_tags = join( ", ", @found_tags );
    return ( $concat_tags, $title, $summary );

}

1;
